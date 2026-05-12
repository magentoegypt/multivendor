<?php 
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Framework\Event\Observer;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class SkipQuantityValidator
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

    public function beforeValidate($subject, Observer $observer) {
        $this->bundleExtendHelper->setSkipConfig(true);
        return [$observer];
    }

    public function afterValidate($subject) {
        $this->bundleExtendHelper->setSkipConfig(false);
    }
}