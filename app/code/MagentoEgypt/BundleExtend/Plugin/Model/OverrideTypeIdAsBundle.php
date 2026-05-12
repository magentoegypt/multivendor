<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class OverrideTypeIdAsBundle
{
    /**
     * @var BundleExtendHelper
     */
    private $helper;

    public function __construct(BundleExtendHelper $helper)
    {
        $this->helper = $helper;
    }

    public function afterGetTypeId(Product $subject, $result)
    {
        if ($result === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE
            && $this->helper->getOverrideTypeIdAsBundle()) {
            return BundleType::TYPE_CODE;
        }
        return $result;
    }
}
