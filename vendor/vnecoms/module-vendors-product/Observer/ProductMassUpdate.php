<?php

namespace Vnecoms\VendorsProduct\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class ProductMassUpdate implements ObserverInterface
{
    /**
     * @var ProductResource
     */
    protected $productResource;

    public function __construct(ProductResource $productResource)
    {
        $this->productResource = $productResource;
    }

    /**
     * Modify no Cookies forward object
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $attrData = $observer->getAttributesData();
        $productIds = $observer->getProductIds();
        if(!isset($attrData['vendor_id'])) return;

        $this->updateVendorId($productIds, $attrData['vendor_id']);

        unset($attrData['vendor_id']);
        $observer->setAttributesData($attrData);
    }

    /**
     * @param array $productIds
     * @param string $vendorId
     */
    protected function updateVendorId($productIds, $vendorId){
        $connection = $this->productResource->getConnection();
        $where = ['entity_id IN (?)' => $productIds];
        $connection->update(
            $this->productResource->getTable('catalog_product_entity'),
            ['vendor_id' => $vendorId],
            $where
        );
    }
}
