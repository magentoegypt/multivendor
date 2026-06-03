<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Mapping;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\EntityMapFactory;
use MagentoEgypt\OdooConnector\Model\ResourceModel\EntityMap as EntityMapResource;
use MagentoEgypt\OdooConnector\Model\ResourceModel\EntityMap\CollectionFactory;

/**
 * The anti-duplication keystone (architecture doc section 2).
 *
 * All create/update flows consult the map first and only ever create a
 * far-side record when no mapping exists for the natural key + scope. The
 * link() upsert is keyed on the unique (entity_type, magento_natural_key,
 * website_id) index, so concurrent events for the same key collapse to one
 * row instead of spawning duplicates.
 */
class MapManager
{
    /**
     * Columns link() will update on an existing row when present in the payload.
     */
    private const UPDATABLE = [
        'magento_id',
        'odoo_model',
        'odoo_id',
        'store_id',
        'odoo_company_id',
        'vendor_id',
        'last_direction',
        'magento_checksum',
        'odoo_checksum',
        'magento_updated_at',
        'odoo_write_date',
        'sync_status',
        'last_correlation_id',
    ];

    private EntityMapFactory $mapFactory;
    private EntityMapResource $resource;
    private CollectionFactory $collectionFactory;
    private ResourceConnection $resourceConnection;

    public function __construct(
        EntityMapFactory $mapFactory,
        EntityMapResource $resource,
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->mapFactory = $mapFactory;
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function findByNaturalKey(string $entityType, string $naturalKey, int $websiteId = 0): ?EntityMap
    {
        return $this->firstOf([
            'entity_type' => $entityType,
            'magento_natural_key' => $naturalKey,
            'website_id' => $websiteId,
        ]);
    }

    public function findByMagentoId(string $entityType, string $magentoId, int $websiteId = 0): ?EntityMap
    {
        return $this->firstOf([
            'entity_type' => $entityType,
            'magento_id' => $magentoId,
            'website_id' => $websiteId,
        ]);
    }

    public function findByOdooId(string $entityType, string $odooModel, int $odooId, int $websiteId = 0): ?EntityMap
    {
        return $this->firstOf([
            'entity_type' => $entityType,
            'odoo_model' => $odooModel,
            'odoo_id' => $odooId,
            'website_id' => $websiteId,
        ]);
    }

    /**
     * Idempotent link/upsert. Requires entity_type + magento_natural_key.
     * Returns the resulting (single) map row.
     *
     * @param array<string, mixed> $data
     */
    public function link(array $data): EntityMap
    {
        if (empty($data['entity_type']) || !isset($data['magento_natural_key'])) {
            throw new \InvalidArgumentException('link() requires entity_type and magento_natural_key.');
        }
        $data['website_id'] = (int)($data['website_id'] ?? 0);

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(EntityMapResource::TABLE);

        // Heal post-migration ID drift: if another mapping already holds this odoo_id
        // for the same (entity_type, odoo_model, website) under a DIFFERENT natural
        // key, release it (null its odoo_id, mark pending) so this row can claim the
        // id. Otherwise the unique (entity_type, odoo_model, odoo_id, website_id)
        // index aborts the upsert — the inventory/customer collision seen after the
        // Odoo 16->19 migration reassigned record IDs. The released row re-attaches
        // by natural key on its next push.
        if (!empty($data['odoo_id']) && !empty($data['odoo_model'])) {
            $connection->update(
                $table,
                ['odoo_id' => null, 'sync_status' => EntityMap::STATUS_PENDING],
                [
                    'entity_type = ?' => (string)$data['entity_type'],
                    'odoo_model = ?' => (string)$data['odoo_model'],
                    'odoo_id = ?' => (int)$data['odoo_id'],
                    'website_id = ?' => (int)$data['website_id'],
                    'magento_natural_key <> ?' => (string)$data['magento_natural_key'],
                ]
            );
        }

        $updateFields = array_values(array_filter(
            self::UPDATABLE,
            static fn (string $column): bool => array_key_exists($column, $data)
        ));

        // Unique (entity_type, magento_natural_key, website_id) absorbs the race.
        $connection->insertOnDuplicate($table, $data, $updateFields);

        return $this->findByNaturalKey(
            (string)$data['entity_type'],
            (string)$data['magento_natural_key'],
            (int)$data['website_id']
        ) ?? $this->mapFactory->create();
    }

    public function save(EntityMap $map): EntityMap
    {
        $this->resource->save($map);

        return $map;
    }

    /**
     * True when an inbound change merely echoes our own last write to that side
     * (checksum matches) — used to stop the bidirectional ping-pong loop.
     */
    public function isEcho(EntityMap $map, string $incomingChecksum, string $side): bool
    {
        $stored = $side === 'odoo' ? $map->getData('odoo_checksum') : $map->getData('magento_checksum');

        return $stored !== null && hash_equals((string)$stored, $incomingChecksum);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function firstOf(array $filters): ?EntityMap
    {
        $collection = $this->collectionFactory->create();
        foreach ($filters as $field => $value) {
            $collection->addFieldToFilter($field, $value);
        }
        $collection->setPageSize(1)->setCurPage(1);

        /** @var EntityMap $item */
        $item = $collection->getFirstItem();

        return $item->getId() ? $item : null;
    }
}
