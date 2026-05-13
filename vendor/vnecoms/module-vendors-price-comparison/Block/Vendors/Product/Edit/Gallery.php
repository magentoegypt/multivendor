<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Block\Vendors\Product\Edit;

class Gallery extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
     ){
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }
    
    /**
     * Hide the gallery block if the current product is selected and sell product.
     * 
     * @see \Magento\Framework\View\Element\AbstractBlock::_prepareLayout()
     */
    protected function _prepareLayout(){
        if(!$this->getProduct()->getData('select_from_product_id')) return;
        
        $config = $this->getParentBlock()->getData('config');
        $config['canShow'] = false;
        $this->getParentBlock()->setData('config',$config);
        return parent::_prepareLayout();
    }
    
    
    /**
     * Get Product
     * 
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct(){
        return $this->_coreRegistry->registry('product');
    }
}
