<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Quote\Model\Quote\Item;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class SkipConfigQuoteItem
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

    public function afterGetQtyOptions(Item $subject, $qtyOptions)
    {
        if ($this->bundleExtendHelper->getSkipConfig()) {
            foreach ($qtyOptions as $key => $option) {
                if (is_object($option->getProduct()) && $option->getProduct()->getTypeId() == Configurable::TYPE_CODE) {
                    unset($qtyOptions[$key]);
                }
            }
        }
        return $qtyOptions;
    }
}