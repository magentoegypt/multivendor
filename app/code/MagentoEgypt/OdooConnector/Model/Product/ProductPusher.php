<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;

/**
 * Shared "push one product to Odoo" service used by both the CLI command and
 * the queue consumer. Idempotent: an existing odoo_id triggers a write, never
 * a second create.
 */
class ProductPusher
{
    private const ENTITY_TYPE = 'product';
    private const ODOO_MODEL = 'product.template';

    private ProductRepositoryInterface $productRepository;
    private OdooClient $odooClient;
    private ProductPushMapper $pushMapper;
    private MapManager $mapManager;
    private Config $config;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        OdooClient $odooClient,
        ProductPushMapper $pushMapper,
        MapManager $mapManager,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->odooClient = $odooClient;
        $this->pushMapper = $pushMapper;
        $this->mapManager = $mapManager;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array{action: string, odoo_id: int, sku: string}
     */
    public function pushById(int $magentoId, string $correlationId): array
    {
        return $this->push($this->productRepository->getById($magentoId), $correlationId);
    }

    /**
     * @return array{action: string, odoo_id: int, sku: string}
     */
    public function push(ProductInterface $product, string $correlationId): array
    {
        $sku = (string)$product->getSku();
        $companyId = $this->resolveCompanyId($product);
        $values = $this->pushMapper->toOdooValues($product);
        // Multi-company scoping: assign the product to the website's configured Odoo
        // company, or make it a global/shared record (company_id = false) when no
        // per-website company id is set. Sending false (rather than omitting the key)
        // is deliberate — omitting it would let Odoo default the product to the API
        // user's company instead of keeping it global.
        $values['company_id'] = $companyId ?? false;

        $map = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $sku, 0);
        $existingOdooId = ($map !== null && $map->getData('odoo_id')) ? (int)$map->getData('odoo_id') : null;

        // Re-attach to an existing Odoo product by SKU when the map has no link
        // (idempotent vs Odoo even after the entity map is cleared — never duplicates).
        if ($existingOdooId === null) {
            $found = $this->odooClient->executeKw(self::ODOO_MODEL, 'search', [[['default_code', '=', $sku]]], ['limit' => 1]);
            if (is_array($found) && isset($found[0])) {
                $existingOdooId = (int)$found[0];
            }
        }

        if ($existingOdooId !== null) {
            try {
                $this->odooClient->executeKw(self::ODOO_MODEL, 'write', [[$existingOdooId], $values]);
                $odooId = $existingOdooId;
                $action = 'update';
            } catch (\MagentoEgypt\OdooConnector\Model\Api\OdooException $e) {
                if (!$e->isMissingRecord()) {
                    throw $e;
                }
                // Stale link (product gone after the Odoo migration) — re-attach by SKU.
                $found = $this->odooClient->executeKw(self::ODOO_MODEL, 'search', [[['default_code', '=', $sku]]], ['limit' => 1]);
                if (is_array($found) && isset($found[0])) {
                    $odooId = (int)$found[0];
                    $this->odooClient->executeKw(self::ODOO_MODEL, 'write', [[$odooId], $values]);
                    $action = 'update';
                } else {
                    $odooId = (int)$this->odooClient->executeKw(self::ODOO_MODEL, 'create', [$values]);
                    $action = 'create';
                }
            }
        } else {
            $odooId = (int)$this->odooClient->executeKw(self::ODOO_MODEL, 'create', [$values]);
            $action = 'create';
        }

        $checksum = $this->pushMapper->expectedOdooChecksum($values);
        $this->mapManager->link([
            'entity_type' => self::ENTITY_TYPE,
            'magento_natural_key' => $sku,
            'magento_id' => (string)$product->getId(),
            'odoo_model' => self::ODOO_MODEL,
            'odoo_id' => $odooId,
            'magento_checksum' => $checksum,
            'odoo_checksum' => $checksum,
            'last_direction' => EntityMap::DIRECTION_M2O,
            'sync_status' => EntityMap::STATUS_LINKED,
            'website_id' => 0,
            'odoo_company_id' => $companyId,
            'last_correlation_id' => $correlationId,
        ]);

        return ['action' => $action, 'odoo_id' => $odooId, 'sku' => $sku];
    }

    /**
     * Resolve the Odoo company for this product from its website(s).
     *
     * Reads odooconnector/connection/odoo_company_id at each of the product's
     * websites (store scope, so a value set only at default scope is inherited).
     * Returns the single configured company when the product's websites agree;
     * returns null — i.e. a global/shared Odoo product — when no website has a
     * company id set, or when websites map to different companies (a single
     * product.template cannot belong to more than one company).
     */
    private function resolveCompanyId(ProductInterface $product): ?int
    {
        $companyIds = [];
        foreach ((array)$product->getWebsiteIds() as $websiteId) {
            $storeId = null;
            try {
                $store = $this->storeManager->getWebsite((int)$websiteId)->getDefaultStore();
                $storeId = $store ? (int)$store->getId() : null;
            } catch (\Throwable $e) {
                $storeId = null;
            }
            $companyId = $this->config->getOdooCompanyId($storeId);
            if ($companyId !== null) {
                $companyIds[$companyId] = $companyId;
            }
        }

        return count($companyIds) === 1 ? (int)reset($companyIds) : null;
    }
}
