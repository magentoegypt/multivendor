<?php
namespace Vnecoms\VendorsCategory\Model\Layer\Filter;

class Decimal extends \Magento\Catalog\Model\Layer\Filter\Decimal
{
    protected $vendorFilterItemFactory;

    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\DecimalFactory $dataProviderFactory,
        \Vnecoms\VendorsCategory\Model\Layer\Filter\ItemFactory $vendorFilterItemFactory,
        array $data = []
    ) {
    
        $this->vendorFilterItemFactory = $vendorFilterItemFactory;

        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $priceCurrency, $dataProviderFactory, $data);
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
