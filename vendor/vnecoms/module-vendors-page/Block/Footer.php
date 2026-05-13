<?php
namespace Vnecoms\VendorsPage\Block;

class Footer extends \Vnecoms\VendorsPage\Block\Head
{
    /**
     * Get Head Html
     * 
     * @return string
     */
    public function getFooterHtml(){
        return $this->helper->getVendorFooterHtml($this->getVendorId());
    }

    /**
     * @see \Magento\Framework\View\Element\Template::_toHtml()
     */
    protected function _toHtml(){
        if(!$this->getVendorId() || !$this->getFooterHtml()) return '';
        return \Magento\Framework\View\Element\Template::_toHtml();
    }
}
