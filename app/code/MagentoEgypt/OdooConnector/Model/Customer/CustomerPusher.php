<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;

/**
 * Shared "push one customer to Odoo res.partner" service for the CLI and the
 * queue consumer. Idempotent via the map; also attaches to a pre-existing Odoo
 * partner with the same email (rather than creating a duplicate).
 */
class CustomerPusher
{
    private const ENTITY_TYPE = 'customer_buyer';
    private const ODOO_MODEL = 'res.partner';

    private CustomerRepositoryInterface $customerRepository;
    private OdooClient $odooClient;
    private CustomerPushMapper $pushMapper;
    private MapManager $mapManager;
    private Config $config;
    private GroupRepositoryInterface $groupRepository;

    /** @var array<string, int|null> */
    private array $countryCache = [];
    /** @var array<string, int|null> */
    private array $tagCache = [];

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        OdooClient $odooClient,
        CustomerPushMapper $pushMapper,
        MapManager $mapManager,
        Config $config,
        GroupRepositoryInterface $groupRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->odooClient = $odooClient;
        $this->pushMapper = $pushMapper;
        $this->mapManager = $mapManager;
        $this->config = $config;
        $this->groupRepository = $groupRepository;
    }

    /**
     * @return array{action: string, odoo_id: int, email: string}
     */
    public function pushById(int $magentoId, string $correlationId): array
    {
        return $this->push($this->customerRepository->getById($magentoId), $correlationId);
    }

    /**
     * @return array{action: string, odoo_id: int, email: string}
     */
    public function push(CustomerInterface $customer, string $correlationId): array
    {
        $email = strtolower(trim((string)$customer->getEmail()));
        $websiteId = (int)$customer->getWebsiteId();
        $companyId = $this->config->getOdooCompanyId($customer->getStoreId());
        $values = $this->pushMapper->toOdooValues($customer);
        if ($companyId !== null) {
            $values['company_id'] = $companyId;
        }

        // Customer Group -> raw value + an Odoo partner tag (res.partner.category).
        $groupCode = $this->groupCode((int)$customer->getGroupId());
        if ($groupCode !== null) {
            $values['x_magento_customer_group'] = $groupCode;
            $tagId = $this->resolveGroupTagId($groupCode);
            if ($tagId !== null) {
                $values['category_id'] = [[6, 0, [$tagId]]];
            }
        }

        // Billing country ISO code -> Odoo res.country id.
        $countryCode = $this->pushMapper->billingCountryCode($customer);
        if ($countryCode !== null) {
            $countryId = $this->resolveCountryId($countryCode);
            if ($countryId !== null) {
                $values['country_id'] = $countryId;
            }
        }

        $map = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $email, $websiteId);
        $odooId = ($map !== null && $map->getData('odoo_id')) ? (int)$map->getData('odoo_id') : null;

        // Not mapped yet — attach to a pre-existing Odoo partner with this email if one exists.
        if ($odooId === null) {
            $found = $this->odooClient->executeKw(
                self::ODOO_MODEL,
                'search',
                [[['email', '=', (string)$customer->getEmail()]]],
                ['limit' => 1]
            );
            if (is_array($found) && isset($found[0])) {
                $odooId = (int)$found[0];
            }
        }

        if ($odooId !== null) {
            $this->odooClient->executeKw(self::ODOO_MODEL, 'write', [[$odooId], $values]);
            $action = 'update';
        } else {
            $odooId = (int)$this->odooClient->executeKw(self::ODOO_MODEL, 'create', [$values]);
            $action = 'create';
        }

        // Shipping Address -> child delivery contact under the partner.
        $shipping = $this->pushMapper->shippingAddress($customer);
        if ($shipping !== null) {
            $this->upsertShippingChild($odooId, $shipping);
        }

        $checksum = $this->pushMapper->expectedOdooChecksum($values);
        $this->mapManager->link([
            'entity_type' => self::ENTITY_TYPE,
            'magento_natural_key' => $email,
            'magento_id' => (string)$customer->getId(),
            'odoo_model' => self::ODOO_MODEL,
            'odoo_id' => $odooId,
            'magento_checksum' => $checksum,
            'odoo_checksum' => $checksum,
            'last_direction' => EntityMap::DIRECTION_M2O,
            'sync_status' => EntityMap::STATUS_LINKED,
            'website_id' => $websiteId,
            'odoo_company_id' => $companyId,
            'last_correlation_id' => $correlationId,
        ]);

        return ['action' => $action, 'odoo_id' => $odooId, 'email' => $email];
    }

    private function groupCode(int $groupId): ?string
    {
        try {
            $code = (string)$this->groupRepository->getById($groupId)->getCode();
        } catch (\Throwable $e) {
            return null;
        }

        return $code !== '' ? $code : null;
    }

    private function resolveCountryId(string $isoCode): ?int
    {
        $isoCode = strtoupper($isoCode);
        if (array_key_exists($isoCode, $this->countryCache)) {
            return $this->countryCache[$isoCode];
        }
        try {
            $found = $this->odooClient->executeKw('res.country', 'search', [[['code', '=', $isoCode]]], ['limit' => 1]);
            $id = (is_array($found) && isset($found[0])) ? (int)$found[0] : null;
        } catch (\Throwable $e) {
            $id = null;
        }

        return $this->countryCache[$isoCode] = $id;
    }

    private function resolveGroupTagId(string $groupCode): ?int
    {
        $name = 'Magento: ' . $groupCode;
        if (array_key_exists($name, $this->tagCache)) {
            return $this->tagCache[$name];
        }
        try {
            $found = $this->odooClient->executeKw('res.partner.category', 'search', [[['name', '=', $name]]], ['limit' => 1]);
            if (is_array($found) && isset($found[0])) {
                return $this->tagCache[$name] = (int)$found[0];
            }
            $id = (int)$this->odooClient->executeKw('res.partner.category', 'create', [['name' => $name]]);

            return $this->tagCache[$name] = $id;
        } catch (\Throwable $e) {
            return $this->tagCache[$name] = null;
        }
    }

    private function upsertShippingChild(int $parentOdooId, AddressInterface $shipping): void
    {
        try {
            $vals = $this->pushMapper->addressFields($shipping);
            $countryCode = trim((string)$shipping->getCountryId());
            if ($countryCode !== '') {
                $cid = $this->resolveCountryId($countryCode);
                if ($cid !== null) {
                    $vals['country_id'] = $cid;
                }
            }
            $name = trim(($shipping->getFirstname() ?? '') . ' ' . ($shipping->getLastname() ?? ''));
            $vals['name'] = $name !== '' ? $name : 'Shipping Address';
            $vals['type'] = 'delivery';
            $vals['parent_id'] = $parentOdooId;

            $existing = $this->odooClient->executeKw(
                'res.partner',
                'search',
                [[['parent_id', '=', $parentOdooId], ['type', '=', 'delivery']]],
                ['limit' => 1]
            );
            if (is_array($existing) && isset($existing[0])) {
                $this->odooClient->executeKw('res.partner', 'write', [[(int)$existing[0]], $vals]);
            } else {
                $this->odooClient->executeKw('res.partner', 'create', [$vals]);
            }
        } catch (\Throwable $e) {
            // Non-fatal: the main partner already synced.
        }
    }
}
