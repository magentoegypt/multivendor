<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Plugin\Product;

class Action
{
    protected $attributes = ["approval", "status"];

    /**
     * @var \Vnecoms\VendorsPriceComparison\Model\Process
     */
    protected $process;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $_catalogProductTypeConfigurable;

    /**
     * AbstractEntity constructor.
     * @param \Vnecoms\VendorsPriceComparison\Model\Process $process
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     */
    public function __construct(
        \Vnecoms\VendorsPriceComparison\Model\Process $process,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
    ) {
        $this->process = $process;
        $this->_collectionFactory = $collectionFactory;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
    }

    /**
     * Before prepare product collection handler
     *
     * @param \Magento\Catalog\Model\Layer $subject
     * @param \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateAttributes(
        \Magento\Catalog\Model\ResourceModel\Product\Action $subject,
        $result,
        $entityIds,
        $attrData,
        $storeId
    ) {
        $check = false;
        foreach ($attrData as $attributeCode => $value) {
            if (in_array($attributeCode, $this->attributes)) {
                $check = true;
            }
        }
        if (!$check) return;

        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToSelect("select_from_product_id");
        $collection->addAttributeToFilter('entity_id', ["IN" => $entityIds]);

        foreach ($collection as $object) {
            $mainProductId = $object->getData('select_from_product_id')?
                $object->getData('select_from_product_id'):$object->getId();
            $this->process->saveQueueByProductIdType($mainProductId, "update");

            //process parent product
            $parentByChilds = $this->_catalogProductTypeConfigurable->getParentIdsByChild($object->getId());
            if ($parentByChilds) {
                $collectionParents = $this->_collectionFactory->create();
                $collectionParents->addAttributeToSelect("*");
                $collectionParents->addAttributeToFilter('entity_id', ["IN" => $parentByChilds]);

                foreach ($collectionParents as $product) {
                    $mainProductId = $product->getData('select_from_product_id')?
                        $product->getData('select_from_product_id'):$product->getId();
                    $this->process->saveQueueByProductIdType($mainProductId, "update");
                }
            }
        }
    }

}
