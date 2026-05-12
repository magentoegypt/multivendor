<?php
namespace MagentoEgypt\BundleExtend\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\CatalogRule\Model\ResourceModel\Product\CollectionProcessor;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\Amount\AmountFactory;

class Discount extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var CollectionProcessor
     */
    private $catalogRuleProcessor;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    private $catalogProduct;

    /**
     * @var AmountFactory
     */
    protected $amountFactory;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        Context $context,
        CollectionProcessor $catalogRuleProcessor,
        AmountFactory $amountFactory,
        \Magento\Catalog\Helper\Product $catalogProduct
    ) {
        parent::__construct($context);
        $this->catalogProduct = $catalogProduct;
        $this->amountFactory = $amountFactory;
        $this->catalogRuleProcessor = $catalogRuleProcessor ?? ObjectManager::getInstance()->get(CollectionProcessor::class);
    }

    private function calcFinalPrice($price, $discountType, $discountAmount)
    {
        if (!$discountAmount) {
            return 0;
        }
        if ($discountType === 'fixed') {
            return max(0, $discountAmount);
        } elseif ($discountType === 'percent') {
            return max(0, $price * $discountAmount / 100);
        }
        return 0;
    }

    protected function getOptions($product, $stripSelection = false)
    {
        if (!$this->options) {
            $typeInstance = $product->getTypeInstance();
            $typeInstance->setStoreFilter($product->getStoreId(), $product);

            $optionCollection = $typeInstance->getOptionsCollection($product);

            $selectionCollection = $typeInstance->getSelectionsCollection(
                $typeInstance->getOptionsIds($product),
                $product
            );
            $this->catalogRuleProcessor->addPriceData($selectionCollection);
            $selectionCollection->addTierPriceData();

            $this->options = $optionCollection->appendSelections(
                $selectionCollection,
                $stripSelection,
                $this->catalogProduct->getSkipSaleableCheck()
            );
        }

        return $this->options;
    }

    protected function getDiscount($product, $isMin = true)
    {
        $discount = 0;
        $options = $this->getOptions($product);
        foreach ($options as $option) {
            $minList = [];
            foreach ($option->getSelections() as $selection) {
                if ($selection->isSalable()) {
                    $minList[] = $selection->getFinalPrice();
                }
            }
            $final = $isMin ? min($minList) : max($minList);
            $discount += $this->calcFinalPrice(
                $final, $option->getData('discount_type'), $option->getData('discount_amount')
            );
        }
        return $discount;
    }

    public function getPrice($product, $price, $isMin = true)
    {
        $discountAmount = $this->getDiscount($product, $isMin);
        return $this->amountFactory->create($price - $discountAmount);
    }
}