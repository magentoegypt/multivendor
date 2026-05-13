<?php
namespace Vnecoms\VendorsCategory\Model\Layer\Filter;

class Attribute extends \Magento\Catalog\Model\Layer\Filter\Attribute
{
    protected $vendorFilterItemFactory;

    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Vnecoms\VendorsCategory\Model\Layer\Filter\ItemFactory $vendorFilterItemFactory,
        array $data = []
    ) {
    
        $this->vendorFilterItemFactory = $vendorFilterItemFactory;
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $filterAttributeFactory, $string, $tagFilter, $data);
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
