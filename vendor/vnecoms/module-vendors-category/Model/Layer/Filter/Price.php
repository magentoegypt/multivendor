<?php
namespace Vnecoms\VendorsCategory\Model\Layer\Filter;

class Price extends \Vnecoms\VendorsLayerNavigation\Model\Layer\Filter\Price
{
    protected $vendorFilterItemFactory;

    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        \Vnecoms\VendorsCategory\Model\Layer\Filter\ItemFactory $vendorFilterItemFactory,
        array $data = []
    ) {
    
        $this->vendorFilterItemFactory = $vendorFilterItemFactory;

        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $resource, $customerSession, $priceAlgorithm, $priceCurrency, $algorithmFactory, $dataProviderFactory, $data);
    }

    /**
     * Create filter item object
     *
     * @param   string $label
     * @param   mixed $value
     * @param   int $count
     * @return  \Magento\Catalog\Model\Layer\Filter\Item
     */
    protected function _createItem($label, $value, $count = 0)
    {
        return $this->vendorFilterItemFactory->create()
            ->setFilter($this)
            ->setLabel($label)
            ->setValue($value)
            ->setCount($count);
    }
}
