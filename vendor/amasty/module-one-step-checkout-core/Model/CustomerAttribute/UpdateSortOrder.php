<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\CustomerAttribute;

use Amasty\CheckoutCore\Model\Field\Form\GetMaxSortOrder;
use Amasty\CheckoutCore\Model\Field\Form\Processor\CustomerAttributes;
use Magento\Customer\Model\Attribute;
use Magento\Framework\Exception\LocalizedException;

class UpdateSortOrder
{
    public const SORT_ORDER_STEP = 10;

    /**
     * @var GetMaxSortOrder
     */
    private $getMaxSortOrder;

    public function __construct(GetMaxSortOrder $getMaxSortOrder)
    {
        $this->getMaxSortOrder = $getMaxSortOrder;
    }

    /**
     * @param Attribute $attribute
     * @return void
     * @throws LocalizedException
     */
    public function execute(Attribute $attribute): void
    {
        if ($this->canSetSortOrder($attribute)) {
            $sortOrder = $this->getMaxSortOrder->execute() + self::SORT_ORDER_STEP;
            $attribute->setData('sorting_order', $sortOrder);
            $attribute->setData('sort_order', $sortOrder + CustomerAttributes::SORT_ORDER_OFFSET);
        }
    }

    private function canSetSortOrder(Attribute $attribute): bool
    {
        if (!$attribute->getData('used_in_product_listing')) {
            return false;
        }

        $sortOrder = $attribute->getData('sorting_order');
        $isObjectNew = $attribute->isObjectNew();

        if ($isObjectNew && (int) $sortOrder === 0) {
            return true;
        }

        return !$isObjectNew && $sortOrder !== '0' && (empty($sortOrder) || ctype_space($sortOrder));
    }
}
