<?php 
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Bundle\Model\ResourceModel\Option\Collection;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class BundleOptionCollection
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

    public function beforeAppendSelections(Collection $subject, $selectionsCollection, $stripBefore = false, $appendAll = true) {
        $this->bundleExtendHelper->setSkipRequiredCheck(true);
        return [$selectionsCollection, $stripBefore, $appendAll];
    }

    public function afterAppendSelections(Collection $subject, $result) {
        $this->bundleExtendHelper->setSkipRequiredCheck(false);
        return $result;
    }
}