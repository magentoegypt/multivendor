<?php
namespace Vnecoms\VendorsPage\Block;

use Magento\Framework\View\Element\Template\Context;
use Vnecoms\VendorsPage\Helper\Data as Helper;
use Magento\Framework\Registry;

class Head extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    
    /**
     * @param Context $context
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Helper $helper,
        Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->coreRegistry = $coreRegistry;
    }
    
    /**
     * Get Head Html
     * 
     * @return string
     */
    public function getHeadHtml(){
        return $this->helper->getVendorHeadHtml($this->getVendorId());
    }
    
    /**
     * Get current vendor Id
     * 
     * @return int
     */
    public function getVendorId(){
        $product = $this->coreRegistry->registry('product');
        if($product) return $product->getVendorId();
        $vendor = $this->coreRegistry->registry('vendor');
        return $vendor?$vendor->getId():false;
    }
    
	public function _prepareLayout(){
		$this->pageConfig->addBodyClass('vendor-page');
		$this->pageConfig->addBodyClass('page-products');
		$this->pageConfig->addBodyClass('page-with-filter');
		
		return parent::_prepareLayout();
	}
	
    /**
     * @see \Magento\Framework\View\Element\Template::_toHtml()
     */
    protected function _toHtml(){
        if(!$this->getVendorId() || !$this->getHeadHtml()) return '';
        return parent::_toHtml();
    }
}
