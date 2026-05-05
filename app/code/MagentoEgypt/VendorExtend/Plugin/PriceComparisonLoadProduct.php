<?php 
namespace MagentoEgypt\VendorExtend\Plugin;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Vnecoms\VendorsProduct\Helper\Data as ProductHelper;

class PriceComparisonLoadProduct
{
	/**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

	/**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

	/**
     * @var Visibility
     */
    protected $productVisibility;

	/**
     * @var Config
     */
    protected $catalogConfig;

	/**
     * @var ProductHelper
     */
    protected $_productHelper;

	/**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockFilter;
    
    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $productVisibility
     * @param Config $catalogConfig
     * @param ProductHelper $_productHelper
     * @param \Magento\CatalogInventory\Helper\Stock $stockFilter
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
		Visibility $productVisibility,
		Config $catalogConfig,
		ProductHelper $_productHelper,
		\Magento\CatalogInventory\Helper\Stock $stockFilter,
		CollectionFactory $productCollectionFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->productCollectionFactory = $productCollectionFactory;
		$this->productVisibility = $productVisibility;
		$this->catalogConfig = $catalogConfig;
		$this->_productHelper = $_productHelper;
		$this->stockFilter = $stockFilter;
    }

	public function aroundGetProductCollection(\Vnecoms\VendorsPriceComparison\Model\LoadProduct $subject, callable $proceed, $dataPost)
	{
		$return = [];
		$product = $this->getProduct();
		$parentId = $product->getData('select_from_product_id');
		if(empty($parentId)) return $proceed($dataPost);
			
		$productCollection = $this->productCollectionFactory->create();
		$productCollection->addAttributeToSelect('vendor_id')
			->addAttributeToFilter([
				['attribute'=>'entity_id','eq' => $parentId]],
				['attribute'=>'select_from_product_id','eq'=> $parentId]
			)
			->addAttributeToFilter('entity_id', ['neq' => $product->getId()])
			->addAttributeToFilter('approval',['in' => $this->_productHelper->getAllowedApprovalStatus()]);

		$productCollection->addAttributeToSelect($this->catalogConfig->getProductAttributes())
			->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->setVisibility($this->productVisibility->getVisibleInCatalogIds());
		$this->stockFilter->addInStockFilterToCollection($productCollection);

		return $productCollection;
	}

	/**
     * Get current product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct(){
        return $this->coreRegistry->registry('product');
    }
}