<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Bundle\Model\CartItemProcessor;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartItemInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class CartItemProcessorWrapper
{
    /**
     * @var BundleExtendHelper
     */
    private $helper;

    public function __construct(BundleExtendHelper $helper)
    {
        $this->helper = $helper;
    }

    public function aroundProcessOptions(
        CartItemProcessor $subject,
        callable $proceed,
        CartItemInterface $cartItem
    ) {
        if ($cartItem->getProductType() !== BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return $proceed($cartItem);
        }

        $origType = $cartItem->getProductType();
        if ($cartItem instanceof DataObject) {
            $cartItem->setData('product_type', BundleType::TYPE_CODE);
        }
        $this->helper->setOverrideTypeIdAsBundle(true);
        try {
            return $proceed($cartItem);
        } finally {
            $this->helper->setOverrideTypeIdAsBundle(false);
            if ($cartItem instanceof DataObject) {
                $cartItem->setData('product_type', $origType);
            }
        }
    }
}
