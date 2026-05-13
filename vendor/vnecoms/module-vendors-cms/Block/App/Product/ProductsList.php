<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsCms\Block\App\Product;

use Vnecoms\VendorsProduct\Model\Source\Approval as ProductApproval;

/**
 * Catalog Products List widget block
 * Class ProductsList.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductsList extends \Magento\CatalogWidget\Block\Product\ProductsList implements \Vnecoms\VendorsCms\Block\App\AppInterface
{
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsCms::app/product/grid.phtml';
    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor;

    /**
     * Get vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        $vendor = $this->_coreRegistry->registry('vendor');
        if(!$vendor && $product = $this->_coreRegistry->registry('product')){
            if($vendorId = $product->getVendorId()){
                $vendor = \Magento\Framework\App\ObjectManager::getInstance()
					->create('Vnecoms\Vendors\Model\Vendor')->load($vendorId);
            }
        }
        return $vendor;
    }

    /**
     * @return $this
     */
    public function createCollection()
    {
        $collection = parent::createCollection();

        return $collection->addFieldToFilter('vendor_id', $this->getVendor()->getId())
            ->addAttributeToFilter('approval',ProductApproval::STATUS_APPROVED)
            ->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
    }
}
