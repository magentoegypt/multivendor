<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class QuoteItemPrice
{
    public function afterGetConvertedPrice(AbstractItem $subject, $price)
    {
        return $this->maybeOverridePrice($subject, $price);
    }

    public function afterGetPrice(AbstractItem $subject, $price)
    {
        return $this->maybeOverridePrice($subject, $price);
    }

    private function maybeOverridePrice(AbstractItem $subject, $price)
    {
        if (!$this->isNewBundleContext($subject)) {
            return $price;
        }

        $option = $subject->getOptionByCode('bundle_selection_attributes');
        if (!$option) {
            return $price;
        }

        $optionData = json_decode($option->getValue() ?? '', true);
        if (isset($optionData['price'])) {
            return $optionData['price'];
        }

        return max(0, $price);
    }

    private function isNewBundleContext(AbstractItem $subject): bool
    {
        if ($subject->getProductType() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return true;
        }
        $parent = method_exists($subject, 'getParentItem') ? $subject->getParentItem() : null;
        return $parent && $parent->getProductType() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;
    }
}
