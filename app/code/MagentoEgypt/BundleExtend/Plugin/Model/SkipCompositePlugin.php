<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Catalog\Model\Product;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class SkipCompositePlugin
{
    /**
     * @var BundleExtendHelper
     */
    protected $bundleExtendHelper;

    public function __construct(
        BundleExtendHelper $bundleExtendHelper
    ) {
        $this->bundleExtendHelper = $bundleExtendHelper;
    }

    public function afterIsComposite(Product $subject, $result)
    {
        if ($subject->getTypeId() != \Magento\Bundle\Model\Product\Type::TYPE_CODE && $this->bundleExtendHelper->getSkipComplexCheck()) {
            return false;
        }
        return $result;
    }

    public function afterGetData(Product $subject, $result, $key = '', $index = null)
    {
        if($key !== 'required_options') return $result;
        if ($subject->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE && $this->bundleExtendHelper->getSkipRequiredCheck()) {
            return false;
        }
        return $result;
    }
}