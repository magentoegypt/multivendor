<?php 
namespace MagentoEgypt\PortoExtend\Plugin\Helper;

use Magento\Framework\Data\Collection;

class Megamenu
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;


	public function __construct(
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\Registry $_coreRegistry
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->_coreRegistry = $_coreRegistry;
    }

    public function afterGetFirstLevelCategories($subject, $data){
        if($theme = $this->_coreRegistry->registry('vendor_custom_theme')){
            $collection = $this->categoryCollectionFactory->create();
            $collection->addFieldToFilter('included_in_menu', 1);
            $collection->addFieldToFilter('level', 1);
            $collection->addIsActiveFilter();
            $collection->addVendorFilter($this->_getCurrentVendor());
            $collection->addOrder('level', Collection::SORT_ORDER_ASC);
            $collection->addOrder('position', Collection::SORT_ORDER_ASC);
            $collection->addOrder('parent_id', Collection::SORT_ORDER_ASC);
            $collection->addOrder('category_id', Collection::SORT_ORDER_ASC);
            return $collection;
        }
        return $data;
    }

	/**
     * get current vendor.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    protected function _getCurrentVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }
}