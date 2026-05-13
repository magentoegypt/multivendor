<?php
/**
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProduct\Ui\DataProvider\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider as MagentoProductDataProvider;

/**
 * Class ProductDataProvider
 */
class ProductDataProvider extends MagentoProductDataProvider
{
    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[] $addFieldStrategies
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $collectionFactory, $addFieldStrategies, $addFilterStrategies, $meta, $data);
        /*Join with vendor table.*/
        $this->collection->joinTable(
            ['vendor_entity' => $this->collection->getTable('ves_vendor_entity')],
            "entity_id=vendor_id",
            ['vendor_identifier'=>'vendor_id'],
            null,
            'left'
        );
    }

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'vendor_identifier') {
            $this->getCollection()->getSelect()->where('vendor_entity.vendor_id like ?', '%'.$filter->getValue().'%');
        } else {
            parent::addFilter($filter);
        }
    }
}
