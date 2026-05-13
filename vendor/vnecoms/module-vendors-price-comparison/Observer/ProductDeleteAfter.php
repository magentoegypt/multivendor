<?php

namespace Vnecoms\VendorsPriceComparison\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductDeleteAfter implements ObserverInterface
{
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
     * ProductDeleteAfter constructor.
     * @param \Vnecoms\VendorsPriceComparison\Model\Process $process
     * @param CollectionFactory $collectionFactory
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
     * Save product data for all child products
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product*/
        $product = $observer->getProduct();
        /* If the product is not main product, just return*/

        $mainProductId = $product->getData('select_from_product_id')?
            $product->getData('select_from_product_id'):$product->getId();

        $this->process->saveQueueByProductIdType($mainProductId, "delete");

        //process parent product
        $parentByChilds = $this->_catalogProductTypeConfigurable->getParentIdsByChild($product->getId());
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
