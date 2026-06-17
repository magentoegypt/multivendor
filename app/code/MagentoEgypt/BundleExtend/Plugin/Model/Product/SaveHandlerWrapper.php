<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model\Product;

use Magento\Bundle\Model\Product\SaveHandler;
use Magento\Catalog\Api\Data\ProductInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class SaveHandlerWrapper
{
    /**
     * @var BundleExtendHelper
     */
    private $helper;

    public function __construct(BundleExtendHelper $helper)
    {
        $this->helper = $helper;
    }

    public function aroundExecute(SaveHandler $subject, callable $proceed, $entity, $arguments = [])
    {
        $isNewBundle = $entity instanceof ProductInterface
            && $entity->getTypeId() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;

        if (!$isNewBundle) {
            return $proceed($entity, $arguments);
        }

        // Keep both flags active for the WHOLE save. The bundle SaveHandler persists
        // options and their selections via several core LinkManagement calls in sequence
        // (saveChild, removeChild, addChildren). Each must see the parent as a 'bundle'
        // (overrideTypeId) and must skip the composite-child guard so configurable
        // selections survive. Without wrapping the whole save, the per-child plugin would
        // pop the override off after the first saveChild and the subsequent addChildren()
        // would throw "isn't a bundle product".
        $this->helper->pushOverrideTypeIdAsBundle(true);
        $this->helper->pushSkipComplexCheck(true);
        try {
            return $proceed($entity, $arguments);
        } finally {
            $this->helper->popSkipComplexCheck();
            $this->helper->popOverrideTypeIdAsBundle();
        }
    }
}
