<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Bundle\Model\Product\CopyConstructor\Bundle as BundleCopyConstructor;
use Magento\Catalog\Model\Product;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class CopyConstructorWrapper
{
    /**
     * @var BundleExtendHelper
     */
    private $helper;

    public function __construct(BundleExtendHelper $helper)
    {
        $this->helper = $helper;
    }

    public function aroundBuild(
        BundleCopyConstructor $subject,
        callable $proceed,
        Product $product,
        Product $duplicate
    ) {
        if ($product->getTypeId() !== BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return $proceed($product, $duplicate);
        }

        $this->helper->pushOverrideTypeIdAsBundle(true);
        try {
            return $proceed($product, $duplicate);
        } finally {
            $this->helper->popOverrideTypeIdAsBundle();
        }
    }
}
