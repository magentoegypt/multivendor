<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Model;

use Amasty\CheckoutCore\Api\DeliveryDateStatisticInterface;
use Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery\Collection;
use Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery\CollectionFactory;

class DeliveryDateStatistic implements DeliveryDateStatisticInterface
{
    public const DELIVERY_INFO = [
        'date' => 'Delivery Date',
        'time' => 'Delivery Time',
        'comment' => 'Delivery Comment',
    ];

    public const DELIVERY_KEY = 'delivery';
    public const DELIVERY_COUNT_KEY = 'delivery_total_count';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CollectionFactory
     */
    private $deliveryCollectionFactory;

    public function __construct(ConfigProvider $configProvider, CollectionFactory $deliveryCollectionFactory)
    {
        $this->configProvider = $configProvider;
        $this->deliveryCollectionFactory = $deliveryCollectionFactory;
    }

    /**
     * @param array $quoteIds
     * @param int $quoteTotalCount
     * @return array
     */
    public function collect(array $quoteIds = [], int $quoteTotalCount = 1): array
    {
        $conditions = [];
        $statisticData[self::DELIVERY_KEY] = [];
        $statisticData[self::DELIVERY_COUNT_KEY] = [];
        $isDeliveryRequired = $this->configProvider->isDeliveryDateRequired();

        /** @var Collection $delivery */
        $delivery = $this->deliveryCollectionFactory->create();
        $delivery->addSizeSelectByQuoteIds($quoteIds);
        $totalCount = clone $delivery;
        foreach (self::DELIVERY_INFO as $code => $label) {
            $clone = clone $delivery;
            $clone->addFieldToFilter($code, ['notnull' => true]);
            $size = (int)$clone->fetchItem()->getSize();
            $statisticData[self::DELIVERY_KEY][] = [
                'size' => $size,
                'label' => __($label)->getText(),
                'rate' => $this->getRate($size, $quoteTotalCount)
            ];

            if (!$isDeliveryRequired) {
                $conditions[] = ['notnull' => true];
            }
        }

        if ($isDeliveryRequired) {
            $totalCount->addFieldToFilter('date', ['notnull' => true]);
        } else {
            $totalCount->addFieldToFilter(array_keys(self::DELIVERY_INFO), $conditions);
        }

        $statisticData[self::DELIVERY_COUNT_KEY] = $totalCount->getSize();

        return [
            self::DELIVERY_KEY => $statisticData[self::DELIVERY_KEY],
            self::DELIVERY_COUNT_KEY => $statisticData[self::DELIVERY_COUNT_KEY]
        ];
    }

    /**
     * @param int $size
     * @param int $quoteTotalCount
     * @return float
     */
    private function getRate(int $size, int $quoteTotalCount)
    {
        if (!$size) {
            return 0;
        }

        return round(($size / $quoteTotalCount) * 100, 2);
    }
}
