<?php 
namespace MagentoEgypt\VendorExtend\Plugin\Block\VendorsCategory;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Visibility;

class ListProduct
{
    protected $_productCollectionFactory;
    protected $productVisibility;

    public function __construct(CollectionFactory $productCollectionFactory, Visibility $productVisibility) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->productVisibility = $productVisibility;
    }

    public function beforeGetTotalNumberOfProducts(\Vnecoms\VendorsCategory\Block\Product\ListProduct $subject) {
        if (!$subject->getData('total_num_products')) {
            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToFilter('vendor_id', $this->getVendor()->getId())
                ->addAttributeToFilter('approval', ProductApproval::STATUS_APPROVED)
                ->setVisibility($this->productVisibility->getVisibleInCatalogIds());

            $subject->setData('total_num_products', sizeof($collection));
        }

        return [];
    }
}