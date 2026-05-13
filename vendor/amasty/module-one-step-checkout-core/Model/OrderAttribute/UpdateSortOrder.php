<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\OrderAttribute;

use Amasty\CheckoutCore\Model\Field\Form\GetMaxSortOrder;
use Magento\Eav\Model\Attribute;
use Magento\Framework\Exception\LocalizedException;

class UpdateSortOrder
{
    public const FLAG_NO_UPDATE = 'no_update';
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
     * @param \Amasty\Orderattr\Model\Attribute\Attribute $attribute
     * @throws LocalizedException
     * @return void
     */
    public function execute(Attribute $attribute): void
    {
        if ($this->canSetSortOrder($attribute)) {
            $attribute->setSortingOrder($this->getMaxSortOrder->execute() + self::SORT_ORDER_STEP);
        }
    }

    /**
     * @param \Amasty\Orderattr\Model\Attribute\Attribute $attribute
     * @return bool
     */
    private function canSetSortOrder(Attribute $attribute): bool
    {
        $isEnabled = (bool) $attribute->getIsVisibleOnFront();
        if ($attribute->hasData(self::FLAG_NO_UPDATE) || !$isEnabled) {
            return false;
        }

        $sortOrder = $attribute->getSortingOrder();
        $isObjectNew = $attribute->isObjectNew();

        if ($isObjectNew && (int) $sortOrder === 0) {
            return true;
        }

        return !$isObjectNew && $sortOrder !== '0' && (empty($sortOrder) || ctype_space($sortOrder));
    }
}
