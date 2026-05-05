<?php 
namespace MagentoEgypt\VendorExtend\Plugin;

class SelectSaleProductView
{
	/**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

	/**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    
    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
		\Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->productFactory = $productFactory;
    }

	public function aroundExecute(\Vnecoms\VendorsPriceComparison\Observer\ProductViewPredispatch $subject, callable $proceed, \Magento\Framework\Event\Observer $observer)
	{
		if($this->coreRegistry->registry('vnecoms_is_vendor_domain')) {
			$request = $observer->getRequest();
			$productId = $request->getParam('id');
			$quickview = $request->getParam('quickview');
			$product = $this->productFactory->create()->load($productId);
			if(
				$product->getId() > 0 && 
				$product->getData('select_from_product_id') > 0 && 
				empty($quickview) && 
				$this->coreRegistry->registry('vendor')->getId() == $product->getVendorId()
			) {
				return true;
			}
        }

        return $proceed($observer);
	}
}