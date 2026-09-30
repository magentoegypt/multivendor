<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Locale\ListsInterface;
use Magento\Store\Model\ScopeInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use Psr\Log\LoggerInterface;

/**
 * The facts a store page prints under the seller's name, as the website prints
 * them (Vnecoms Block\Profile and its templates, with the theme's overrides):
 *
 *   phone     ves_vendor_entity.telephone (profile/phone.phtml), when the admin
 *             shows seller phones (vendors/profile/show_phone). The theme's
 *             Contact Vendor button dials the same number.
 *   location  the address line (Block\Profile::getAddress() and the theme's
 *             profile/address.phtml, see SellerLocation). Always shown.
 *   sales     "%1 Sales" (profile/sales.phtml): the seller's orders with a
 *             paid amount (VendorsSales Order::getSalesCount()), when the admin
 *             shows sales counts (vendors/profile/show_sales_count).
 *
 * The admin switches are read at default scope, as Vnecoms\Vendors\Helper\Data
 * reads them. Missing Vnecoms tables mean no fact, never an error. Rows are
 * read once per seller per request.
 */
class SellerProfile
{
    public const SHOW_PHONE = 'vendors/profile/show_phone';
    public const SHOW_SALES_COUNT = 'vendors/profile/show_sales_count';
    public const ADDRESS_TEMPLATE = 'vendors/profile/address_template';

    /** Vnecoms' default template (Vnecoms_Vendors etc/config.xml). */
    public const DEFAULT_ADDRESS_TEMPLATE = '{{var region}}, {{var country}}';

    /** @var array<int, array<string, string>|null> vendor id => address and phone columns */
    private array $rows = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ListsInterface $localeLists,
        private readonly RegionFactory $regionFactory,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The telephone the store page shows, as the seller typed it; null when hidden or empty.
     */
    public function phone(int $vendorId): ?string
    {
        if (!$this->scopeConfig->getValue(self::SHOW_PHONE)) {
            return null;
        }
        $phone = trim((string) ($this->row($vendorId)['telephone'] ?? ''));
        //  A number the page's tel: link could not dial is not a way to reach the seller.
        if (preg_replace('/[^0-9+]/', '', $phone) === '') {
            return null;
        }

        return $phone;
    }

    /**
     * The location line in the language of $storeId; null when it would be empty.
     */
    public function location(int $vendorId, int $storeId): ?string
    {
        $row = $this->row($vendorId);
        if ($row === null) {
            return null;
        }

        $line = $this->emulation->run($storeId, function () use ($row, $storeId): string {
            $region = $this->region($row);
            $country = trim((string) $row['country_id']);
            $locale = (string) $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);
            $template = trim((string) $this->scopeConfig->getValue(self::ADDRESS_TEMPLATE));

            $address = SellerLocation::compose(
                $template !== '' ? $template : self::DEFAULT_ADDRESS_TEMPLATE,
                [
                    'street' => (string) $row['street'],
                    'city' => (string) $row['city'],
                    'country' => $country !== '' ? $this->countryName($country, $locale) : '',
                    'region' => $region,
                    'postcode' => (string) $row['postcode'],
                ]
            );

            return SellerLocation::localise(
                $address,
                [(string) $row['city'], $region],
                static fn (string $text): string => (string) __($text)
            );
        });

        return is_string($line) && $line !== '' ? $line : null;
    }

    /**
     * The sales count the store page shows; null when the admin hides it or it cannot be read.
     */
    public function salesCount(int $vendorId): ?int
    {
        if ($vendorId < 1 || !$this->scopeConfig->getValue(self::SHOW_SALES_COUNT)) {
            return null;
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('ves_vendor_sales_order');
            if (!$connection->isTableExists($table)) {
                return null;
            }

            return (int) $connection->fetchOne(
                $connection->select()
                    ->from($table, ['sales_num' => 'COUNT(entity_id)'])
                    ->where('vendor_id = ?', $vendorId)
                    ->where('base_total_paid > 0')
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller sales count unavailable: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * The region as Vnecoms Vendor::getRegion() resolves it: the directory region's name
     * (in the emulated locale) when the id belongs to the seller's country, else the text.
     *
     * @param array<string, string> $row
     */
    private function region(array $row): string
    {
        $regionId = (int) $row['region_id'];
        $text = trim((string) $row['region']);
        if ($regionId < 1 && is_numeric($text)) {
            $regionId = (int) $text;
        }
        if ($regionId > 0) {
            try {
                $region = $this->regionFactory->create()->load($regionId);
                if ($region->getId() && (string) $region->getCountryId() === trim((string) $row['country_id'])) {
                    return trim((string) $region->getName());
                }
            } catch (\Throwable $e) {
                $this->logger->warning('HubApp: seller region unavailable: ' . $e->getMessage());
            }
        }

        //  A numeric region that is not a region of the seller's country stays as typed,
        //  as it does on the website ("999999999").
        return $text;
    }

    private function countryName(string $countryId, string $locale): string
    {
        try {
            return trim((string) $this->localeLists->getCountryTranslation($countryId, $locale !== '' ? $locale : null));
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: country name unavailable: ' . $e->getMessage());

            return '';
        }
    }

    /**
     * @return array<string, string>|null
     */
    private function row(int $vendorId): ?array
    {
        if ($vendorId < 1) {
            return null;
        }
        if (array_key_exists($vendorId, $this->rows)) {
            return $this->rows[$vendorId];
        }
        $this->rows[$vendorId] = null;

        try {
            $connection = $this->resource->getConnection();
            $row = $connection->fetchRow(
                $connection->select()
                    ->from(
                        $this->resource->getTableName('ves_vendor_entity'),
                        ['telephone', 'street', 'city', 'region', 'region_id', 'country_id', 'postcode']
                    )
                    ->where('entity_id = ?', $vendorId)
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller address unavailable: ' . $e->getMessage());

            return null;
        }
        if (!is_array($row)) {
            return null;
        }

        $out = [];
        foreach (['telephone', 'street', 'city', 'region', 'region_id', 'country_id', 'postcode'] as $column) {
            $out[$column] = (string) ($row[$column] ?? '');
        }

        return $this->rows[$vendorId] = $out;
    }
}
