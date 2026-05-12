<?php
namespace MagentoEgypt\BundleExtend\Plugin\Adminhtml\Order\Create;

use Magento\Sales\Block\Adminhtml\Order\Create\Sidebar\AbstractSidebar;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

/**
 * Mirrors Magento\Bundle\Block\Adminhtml\Order\Create\Sidebar for new_bundle.
 * Without this, admin Create-Order sidebar shows a qty input next to new_bundle items
 * (it shouldn't — bundles configure their qty via the option modal) and doesn't mark
 * new_bundle as "configuration required".
 */
class Sidebar
{
    /**
     * @param AbstractSidebar $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject $item
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetItemQty(
        AbstractSidebar $subject,
        \Closure $proceed,
        \Magento\Framework\DataObject $item
    ) {
        if ($item->getProduct()->getTypeId() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return '';
        }
        return $proceed($item);
    }

    /**
     * @param AbstractSidebar $subject
     * @param \Closure $proceed
     * @param string $productType
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsConfigurationRequired(
        AbstractSidebar $subject,
        \Closure $proceed,
        $productType
    ) {
        if ($productType === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return true;
        }
        return $proceed($productType);
    }
}
