<?php

namespace Vnecoms\VendorsProductConfigurable\Ui\DataProvider;

class Attributes extends \Magento\ConfigurableProduct\Ui\DataProvider\Attributes
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $collection;

    /**
     * @var \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler
     */
    private $configurableAttributeHandler;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $configurableAttributeHandler, $meta, $data);
        $this->configurableAttributeHandler = $configurableAttributeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $notAllowedAttribute = $this->getProductHelper()->getNotUsedVendorAttributes();

        $items = [];
        $skippedItems = 0;
        foreach ($this->getCollection()->getItems() as $attribute) {
            if ($this->configurableAttributeHandler->isAttributeApplicable($attribute)) {
                if(in_array($attribute->getAttributeCode(), $notAllowedAttribute)) continue;
                $items[] = $attribute->toArray();
            } else {
                $skippedItems++;
            }
        }
        return [
            'totalRecords' => $this->collection->getSize() - $skippedItems,
            'items' => $items
        ];
    }


    /**
     * @return \Vnecoms\VendorsProduct\Helper\Data
     */
    public function getProductHelper(){
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Vnecoms\VendorsProduct\Helper\Data');
    }
}
