<?php

namespace Vnecoms\Quotation\Observer\Catalog;

use Magento\Framework\Event\ObserverInterface;

class ProductList implements ObserverInterface
{
	/**
	 * @var \Magento\Framework\Event\Manager
	 */
	protected $eventManager;
	
	/**
	 * @var \Vnecoms\Quotation\Helper\Data
	 */
	protected $helper;
	
	/**
	 * @param \Magento\Framework\Event\Manager $eventManager
	 * @param \Vnecoms\Quotation\Helper\Data $helper
	 */
	public function __construct(
		\Magento\Framework\Event\Manager $eventManager,
	    \Vnecoms\Quotation\Helper\Data $helper
	) {
		$this->eventManager   = $eventManager;
		$this->helper         = $helper;
	}
	
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	$block = $observer->getBlock();
    	$transport = $observer->getTransport();
    	$html = $transport->getHtml();
    	
    	$this->eventManager->dispatch('ves_quotation_process_productlist_before', ['block' => $block, 'transport' => $transport]);
    	
    	if($block->getIsAddedQuoteCode()) return;
    	if($block instanceof \Magento\Catalog\Block\Product\ListProduct){
	       $html .= $this->getQuoteProductListHtml(
	           $block->getLoadedProductCollection(),
	           [
	               'containerSelector' => $this->helper->getCategoryContainerSelector(),
	               'addToCartBtnSelector' => $this->helper->getCategoryAddToCartBtnSelector(),
	           ]
           );
	       $block->setIsAddedQuoteCode(true);
    	}
    	
    	$transport->setHtml($html);
    	
    }
    
    /**
     * Get additional html code for product list
     * 
     * @param unknown $productCollection
     * @param string $component
     * @param array $params
     * @return string
     */
    public function getQuoteProductListHtml(
    		$productCollection,
    		$params = [],
    		$component = 'Vnecoms_Quotation/js/catalog/product-list'
	) {
        $productData = [];
        foreach($productCollection as $product){
            $productData['product_'.$product->getId()] = [
                'order_mode' => (bool)$product->getData('ves_enable_order'),
                'quote_mode' => (bool)$product->getData('ves_enable_quote'),
            ];
        }
        $quoteData = [
            'productData' => $productData,
        ];
        $quoteData = array_merge($quoteData, $params);
        
        $quoteData = [
            $component => $quoteData
        ];
        return "<div class=\"ves_quotation_data\" data-mage-init='".json_encode($quoteData)."'></div>";
    }
}
