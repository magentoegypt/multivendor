<?php
namespace MagentoEgypt\BundleExtend\Plugin\Sales\Order;

use Magento\Sales\Model\Order\Item as OrderItem;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

/**
 * Mirrors Magento\Bundle\Model\Sales\Order\Plugin\Item for new_bundle order items.
 * Bundle's plugin only checks against TYPE_BUNDLE, so new_bundle items fall through to
 * the generic qty logic and ship/cancel math is wrong. This plugin fixes that.
 */
class Item
{
    /**
     * @param OrderItem $subject
     * @param float|int $result
     * @return float|int
     */
    public function afterGetQtyToCancel(OrderItem $subject, $result)
    {
        if ($this->isNewBundleContext($subject)) {
            return max($this->getQtyToCancelNewBundle($subject), 0);
        }
        return $result;
    }

    /**
     * @param OrderItem $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsProcessingAvailable(OrderItem $subject, $result)
    {
        if ($this->isNewBundleContext($subject)) {
            return $subject->getSimpleQtyToShip() > $subject->getQtyToCancel();
        }
        return $result;
    }

    private function isNewBundleContext(OrderItem $subject): bool
    {
        if ($subject->getProductType() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return true;
        }
        $parent = $subject->getParentItem();
        return $parent && $parent->getProductType() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;
    }

    /**
     * @param OrderItem $item
     * @return float|int
     */
    private function getQtyToCancelNewBundle(OrderItem $item)
    {
        if ($item->isDummy(true)) {
            return min($item->getQtyToInvoice(), $item->getSimpleQtyToShip());
        }
        return min($item->getQtyToInvoice(), $item->getQtyToShip());
    }
}
