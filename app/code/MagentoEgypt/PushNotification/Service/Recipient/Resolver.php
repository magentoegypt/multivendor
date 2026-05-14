<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Service\Recipient;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\PushNotification\Api\Data\NotificationInterface;
use MagentoEgypt\PushNotification\Model\ResourceModel\Device\CollectionFactory as DeviceCollectionFactory;

class Resolver
{
    /**
     * @var DeviceCollectionFactory
     */
    private $deviceCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(
        DeviceCollectionFactory $deviceCollectionFactory,
        ResourceConnection $resource
    ) {
        $this->deviceCollectionFactory = $deviceCollectionFactory;
        $this->resource = $resource;
    }

    /**
     * Build the device collection for a notification's target audience.
     *
     * @param NotificationInterface $notification
     * @return \MagentoEgypt\PushNotification\Model\ResourceModel\Device\Collection
     */
    public function resolve(NotificationInterface $notification)
    {
        $collection = $this->deviceCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);

        $targetIds = $this->parseTargetIds($notification->getTargetIds());

        switch ($notification->getTargetType()) {
            case NotificationInterface::TARGET_TYPE_CUSTOMERS:
                $collection->addFieldToFilter('customer_id', ['notnull' => true]);
                if ($targetIds) {
                    $collection->addFieldToFilter('customer_id', ['in' => $targetIds]);
                }
                break;
            case NotificationInterface::TARGET_TYPE_VENDORS:
                $collection->addFieldToFilter('vendor_id', ['notnull' => true]);
                if ($targetIds) {
                    $collection->addFieldToFilter('vendor_id', ['in' => $targetIds]);
                }
                break;
            case NotificationInterface::TARGET_TYPE_CUSTOMER_GROUP:
                $customerIds = $this->customerIdsForGroups($targetIds);
                if (!$customerIds) {
                    $collection->addFieldToFilter('customer_id', ['eq' => -1]);
                    break;
                }
                $collection->addFieldToFilter('customer_id', ['in' => $customerIds]);
                break;
            case NotificationInterface::TARGET_TYPE_ALL:
            default:
                break;
        }

        return $collection;
    }

    /**
     * @param string|null $raw
     * @return int[]
     */
    private function parseTargetIds(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('intval', explode(',', $raw))));
    }

    /**
     * @param int[] $groupIds
     * @return int[]
     */
    private function customerIdsForGroups(array $groupIds): array
    {
        if (!$groupIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('customer_entity');
        $select = $connection->select()
            ->from($table, ['entity_id'])
            ->where('group_id IN (?)', $groupIds);
        return array_map('intval', $connection->fetchCol($select));
    }
}
