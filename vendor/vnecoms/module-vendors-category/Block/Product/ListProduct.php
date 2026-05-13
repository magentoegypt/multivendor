<?php

namespace Vnecoms\VendorsCategory\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Vnecoms\VendorsProduct\Model\Source\Approval as ProductApproval;
use Magento\Framework\Exception\NoSuchEntityException;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * Catalog product visibility.
     *
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $productVisibility;

    /**
     * Catalog config.
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $catalogConfig;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->productVisibility = $productVisibility;
        $this->catalogConfig = $context->getCatalogConfig();
        $this->_coreRegistry = $context->getRegistry();
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }

    /**
     * Get current vendor.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    /**
     * @return \Vnecoms\VendorsCategory\Model\Category
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('current_vendor_category');
    }

    /**
     * Retrieve loaded category collection.
     *
     * @return AbstractCollection
     */
    protected function _getProductCollection()
    {
        if ($this->_productCollection === null) {
            //layer support
            $layer = $this->getLayer();

            /* @var $layer \Vnecoms\VendorsCategory\Model\Layer\Category */
            $origCategory = null;
            if ($this->getVendorCategoryId()) {

                try {
                    $category = $this->categoryRepository->get($this->getVendorCategoryId());
                } catch (NoSuchEntityException $e) {
                    $category = null;
                }

                if ($category) {
                    $origCategory = $layer->getCurrentVendorCategory();
                    $layer->setCurrentVendorCategory($category);
                }
            }
            $this->_productCollection = $layer->getProductCollection();

            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());

            if ($origCategory) {
                $layer->setCurrentVendorCategory($origCategory);
            }


            //$this->_productCollection->getSelect()->group('e.entity_id');
        }

        return $this->_productCollection;
    }

    /**
     * Get total number of vendor's product.
     *
     * @return int
     */
    public function getTotalNumberOfProducts()
    {
        if (!$this->getData('total_num_products')) {
            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToFilter('vendor_id', $this->getVendor()->getId())
                ->addAttributeToFilter('approval', array('in' => [ProductApproval::STATUS_APPROVED, ProductApproval::STATUS_PENDING_UPDATE]))
                ->setVisibility($this->productVisibility->getVisibleInCatalogIds());

            $this->setData('total_num_products', sizeof($collection));
        }

        return $this->getData('total_num_products');
    }

    /**
     * Add items link to the menu.
     *
     * @see \Magento\Framework\View\Element\AbstractBlock::_prepareLayout()
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
//        $menuBlock = $this->getLayout()->getBlock('vendor.menu.top');
//        if($menuBlock){
//            $totalProduct = $this->getTotalNumberOfProducts();
//            $menuBlock->addLink(
//                __("Items (%1)",$totalProduct),
//                __("Items (%1)",$totalProduct),
//                '#vendor-products',
//                10
//            );
//        }

        return $this;
    }
}
