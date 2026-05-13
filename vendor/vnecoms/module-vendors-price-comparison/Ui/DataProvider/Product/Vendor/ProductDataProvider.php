<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Ui\DataProvider\Product\Vendor;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Class ProductDataProvider
 */
class ProductDataProvider extends \Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider
{
    /**
     * @var \Vnecoms\VendorsPricecomparison\Helper\Data
     */
    protected $_helper;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var array
     */
    protected $_selectedProductIds = null;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    /**
     * System event manager
     *
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ProductDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Vnecoms\VendorsPriceComparison\Helper\Data $helper
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $addFieldStrategies
     * @param array $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Vnecoms\VendorsPriceComparison\Helper\Data $helper,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_collectionFactory = $collectionFactory;
        $this->_vendorSession = $vendorSession;
        $this->_eventManager = $eventManager;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $collectionFactory,$addFieldStrategies,$addFilterStrategies,$meta,$data);

        $this->collection->addAttributeToSelect('*')
            ->addWebsiteFilter($this->storeManager->getWebsite())
            ->addAttributeToFilter('approval',\Vnecoms\VendorsProduct\Model\Source\Approval::STATUS_APPROVED)
            ->addAttributeToFilter('vendor_id',['neq' => $vendorSession->getVendor()->getId()])
            ->addAttributeToFilter([
                ['attribute'=>'select_from_product_id', 'is' => new \Zend_Db_Expr('NULL')],
                ['attribute'=>'select_from_product_id', 'eq' => 0],
            ], null, 'left')
            ->addAttributeToFilter('visibility',['in' => $productVisibility->getVisibleInCatalogIds()]);

        $ignoreAttributes = $this->_helper->getAttributeSetRestriction();
        if ($ignoreAttributes) {
            $this->collection->addAttributeToFilter('attribute_set_id',['nin' => $ignoreAttributes]);
        }

        $ignoreTypes = $this->_helper->getProductTypeRestriction();
        if ($ignoreTypes) {
            $this->collection->addAttributeToFilter('attribute_set_id',['nin' => $ignoreTypes]);
        }

        /* You can add additional filter using this event.*/
        $this->_eventManager->dispatch('pricecomparison_selectandsell_collection_prepare',['collection' => $this->collection]);
    }

    /**
     * Get all selected product ids
     * @return multitype:
     */
    public function getSelectedProductIds(){
        if($this->_selectedProductIds === null){
            $collectiton = $this->_collectionFactory->create();
            $collectiton->addAttributeToFilter('vendor_id',$this->_vendorSession->getVendor()->getId())
                ->addAttributeToFilter('select_from_product_id',['gt' => 0]);

            $this->_selectedProductIds = $collectiton->getColumnValues('select_from_product_id');
        }

        return $this->_selectedProductIds;
    }

    public function getData()
    {
        $groupId = $this->storeManager->getWebsite()->getDefaultGroupId();
        $storeId = $this->storeManager->getGroup($groupId)->getDefaultStoreId();

        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
            $selectedIds = $this->getSelectedProductIds();
            foreach($this->getCollection() as $product){
                $product->setStoreId($storeId);
                $productUrl = $product->getProductUrl();
                if (preg_match("/vendors/is", $productUrl)) {
                    $productUrl =  preg_replace("/\/vendors/is", "", $productUrl);
                }
                $product->setData('product_url', $productUrl);
                $product->setData('is_sold_product',in_array($product->getId(), $selectedIds));
            }
        }
        $items = $this->getCollection()->toArray();

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];
    }
}
