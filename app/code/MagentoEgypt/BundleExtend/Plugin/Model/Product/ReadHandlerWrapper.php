<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model\Product;

use Magento\Bundle\Model\Product\ReadHandler;
use Magento\Catalog\Api\Data\ProductInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class ReadHandlerWrapper
{
    /**
     * @var BundleExtendHelper
     */
    private $helper;

    public function __construct(BundleExtendHelper $helper)
    {
        $this->helper = $helper;
    }

    public function aroundExecute(ReadHandler $subject, callable $proceed, $entity, $arguments = [])
    {
        $isNewBundle = $entity instanceof ProductInterface
            && $entity->getTypeId() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;

        if (!$isNewBundle) {
            return $proceed($entity, $arguments);
        }

        $this->helper->setOverrideTypeIdAsBundle(true);
        try {
            return $proceed($entity, $arguments);
        } finally {
            $this->helper->setOverrideTypeIdAsBundle(false);
        }
    }
}
