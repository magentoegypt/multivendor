<?php

namespace Vnecoms\VendorsDomain\Block\Vendors\System\Config\Form\Field;

class Domain extends \Vnecoms\VendorsConfig\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $block = $this->getLayout()->createBlock('Vnecoms\VendorsDomain\Block\Vendors\System\Config\Form\Field\Domain\Field')->setElement($element);
        
        return $block->toHtml();
    }
    
}
