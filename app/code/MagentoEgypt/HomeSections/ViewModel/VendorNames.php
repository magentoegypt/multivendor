<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\ViewModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

/**
 * Product -> selling vendor, for the vendor line on a product card.
 *
 * The Figma reference prints the seller above every product title ("TechGear Pro",
 * "HomeStyle Living"). On a marketplace that is not decoration: it is the one piece
 * of information that distinguishes two identical listings from different sellers,
 * and it was missing from every card on the storefront.
 *
 * WHY A BATCH MAP AND NOT A LOOKUP PER PRODUCT
 * --------------------------------------------
 * `catalog_product_entity.vendor_id` is a STATIC attribute — a real column on the
 * entity table — holding `ves_vendor_entity.entity_id`. Resolving it per card
 * through the vendor repository would be one query per product, so ~50 queries on
 * a homepage with nine rails. This install has 25 vendor rows in total, so the
 * whole table is fetched ONCE per request and held here.
 *
 * The name lives in `ves_vendor_entity.company`, which is a plain column rather
 * than one of the module's EAV attributes. Some rows have it empty (vendor 1 on
 * this install), so the url-key falls in behind it — never an empty vendor line
 * and never a bare numeric id.
 *
 * Reads the connection directly rather than going through the Vnecoms vendor
 * collection deliberately: that collection loads the full EAV row set for every
 * vendor, which is the cost this class exists to avoid.
 */
class VendorNames implements ArgumentInterface
{
    /** @var array<int, array{name: string, key: string}>|null */
    private ?array $map = null;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Display name for a vendor id, or null when the product has no seller.
     *
     * Products created in admin rather than by a vendor carry vendor_id 0 — 91 of
     * the 2304 products here. Those genuinely have no seller to name, so the card
     * omits the line rather than inventing one.
     */
    public function getName(mixed $vendorId): ?string
    {
        $id = (int) $vendorId;
        if ($id <= 0) {
            return null;
        }

        return $this->load()[$id]['name'] ?? null;
    }

    /**
     * Storefront URL for the vendor's shop page, or null if it cannot be built.
     */
    public function getUrlKey(mixed $vendorId): ?string
    {
        $id = (int) $vendorId;
        if ($id <= 0) {
            return null;
        }

        return $this->load()[$id]['key'] ?? null;
    }

    /**
     * @return array<int, array{name: string, key: string}>
     */
    private function load(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $this->map = [];

        try {
            $connection = $this->resource->getConnection();
            $table      = $this->resource->getTableName('ves_vendor_entity');
            $select     = $connection->select()->from($table, ['entity_id', 'vendor_id', 'company']);

            foreach ($connection->fetchAll($select) as $row) {
                $key  = trim((string) ($row['vendor_id'] ?? ''));
                $name = trim((string) ($row['company'] ?? ''));

                $this->map[(int) $row['entity_id']] = [
                    'name' => $name !== '' ? $name : $key,
                    'key'  => $key,
                ];
            }
        } catch (LocalizedException | \Throwable $e) {
            /*
             * A missing or renamed vendor table must not take the homepage down —
             * the vendor line is an enhancement to a card that renders fine
             * without it. Logged rather than swallowed so it is still visible.
             */
            $this->logger->warning('HomeSections: vendor name map unavailable: ' . $e->getMessage());
            $this->map = [];
        }

        return $this->map;
    }
}
