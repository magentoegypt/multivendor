<?php

namespace Vnecoms\VendorsCustomTheme\Block\Catalog;

use Vnecoms\VendorsProduct\Model\Source\Approval as ProductApproval;

/**
 * Catalog Products List widget block
 * Class ProductsList.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductsList extends \Magento\CatalogWidget\Block\Product\ProductsList
{
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsCustomTheme::default-grid.phtml';
    
    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;
    
    /**
     * ProductsList constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder
     * @param \Magento\CatalogWidget\Model\Rule $rule
     * @param \Magento\Widget\Helper\Conditions $conditionsHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param array $data
     */
    public function __construct
    (
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder,
        \Magento\CatalogWidget\Model\Rule $rule,
        \Magento\Widget\Helper\Conditions $conditionsHelper,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    )
    {
        $this->_vendorFactory = $vendorFactory;
        $this->moduleManager = $moduleManager;
        /* $context->getCache()->clean(\Magento\Catalog\Model\Product::CACHE_TAG); */
        
        parent::__construct($context, $productCollectionFactory, $catalogProductVisibility, $httpContext, $sqlBuilder, $rule, $conditionsHelper, $data);
    }

    /**
     * Get vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        $vendor = $this->_coreRegistry->registry('vendor');
        if(!$vendor && $product = $this->_coreRegistry->registry('product')){
            if($vendorId = $product->getVendorId()){
                $vendor = $this->_vendorFactory->create()->load($vendorId);
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
        $collection->addFieldToFilter('vendor_id', $this->getVendor()->getId())
            ->addAttributeToFilter('approval',ProductApproval::STATUS_APPROVED)
            ->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
        if($this->moduleManager->isEnabled('Vnecoms_VendorsListingFee')){
            $collection->addFieldToFilter('listing_fee', \Vnecoms\VendorsListingFee\Model\Source\ListingFee::LISTING_FEE_PAID);
        }
        
        /* Filter by category Id*/
        if(
            $this->moduleManager->isEnabled('Vnecoms_VendorsCategory') &&
            $this->getData('category_ids')
        ) {
            $categoryIds = str_replace(" ", '', $this->getData('category_ids'));
            $categoryIds = explode(',', $categoryIds);
            $collection->getSelect()->joinLeft(
                ['vendor_category' => $collection->getTable('ves_vendorscategory_category_product')],
                'vendor_category.product_id = e.entity_id',
                ['position']
            );
            $collection->getSelect()->where('vendor_category.category_id in (?)', $categoryIds);
        }
        
        /* Filter by featured products*/
        if(
            $this->moduleManager->isEnabled('Vnecoms_VendorsFeaturedProduct') &&
            $this->getData('featured_filter')
        ) {
            $today = $this->_localeDate->date(date('y-m-d'));
            $today = $today->format('Y-m-d');
            $collection->getSelect()->joinRight(
                ['featured_product' => $collection->getTable('ves_vendor_featured_product')],
                "featured_product.product_id=e.entity_id",
                ['featured_from', 'featured_to','feature_id' => 'entity_id','featured_order' => 'sort_order']
            );

            $collection->getSelect()
                ->where('featured_product.featured_from is null OR featured_product.featured_from <= "'.$today.'"')
                ->where('featured_product.featured_to is null OR featured_product.featured_to >= "'.$today.'"')
                ->order('featured_product.sort_order ASC');
        }
        
        if($this->getData('product_ids')){
            $productIds = explode(',', $this->getData('product_ids'));
            $collection->addFieldToFilter('entity_id',['in' => $productIds]);
        }
        
        return $collection;
    }
}
