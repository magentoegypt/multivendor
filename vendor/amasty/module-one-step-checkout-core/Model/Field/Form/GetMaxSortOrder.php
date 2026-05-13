<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ModuleEnable;
use Amasty\CheckoutCore\Model\ResourceModel\Field\GetMaxSortOrder as SortOrderResource;
use Magento\Framework\Exception\LocalizedException;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class GetMaxSortOrder
{
    /**
     * @var SortOrderResource
     */
    private $sortOrderResource;

    /**
     * @var GetCustomerAttributes
     */
    private $getCustomerAttributes;

    /**
     * @var GetOrderAttributes
     */
    private $getOrderAttributes;

    /**
     * @var ModuleEnable
     */
    private $moduleEnable;

    public function __construct(
        SortOrderResource $sortOrderResource,
        GetCustomerAttributes $getCustomerAttributes,
        GetOrderAttributes $getOrderAttributes,
        ModuleEnable $moduleEnable
    ) {
        $this->sortOrderResource = $sortOrderResource;
        $this->getCustomerAttributes = $getCustomerAttributes;
        $this->getOrderAttributes = $getOrderAttributes;
        $this->moduleEnable = $moduleEnable;
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function execute(): int
    {
        return max(
            $this->sortOrderResource->execute(),
            $this->extractValueFromCustomerAttributes(),
            $this->extractValueFromOrderAttributes()
        );
    }

    private function extractValueFromCustomerAttributes(): int
    {
        if (!$this->moduleEnable->isCustomerAttributesEnable()) {
            return 0;
        }

        $attributes = $this->getCustomerAttributes->execute(Field::DEFAULT_STORE_ID);

        $maxSortOrder = 0;
        foreach ($attributes as $attribute) {
            if ($attribute->getData('used_in_product_listing')) {
                $maxSortOrder = max($maxSortOrder, (int) $attribute->getData('sorting_order'));
            }
        }

        return $maxSortOrder;
    }

    private function extractValueFromOrderAttributes(): int
    {
        if (!$this->moduleEnable->isOrderAttributesEnable()) {
            return 0;
        }

        $attributes = $this->getOrderAttributes->execute(Field::DEFAULT_STORE_ID);

        $maxSortOrder = 0;
        foreach ($attributes as $attribute) {
            if ($attribute->getIsVisibleOnFront()) {
                $maxSortOrder = max($maxSortOrder, (int) $attribute->getData('sorting_order'));
            }
        }

        return $maxSortOrder;
    }
}
