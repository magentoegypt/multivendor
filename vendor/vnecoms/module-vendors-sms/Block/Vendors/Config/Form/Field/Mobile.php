<?php

namespace Vnecoms\VendorsSms\Block\Vendors\Config\Form\Field;

class Mobile extends \Vnecoms\VendorsConfig\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $element->getElementHtml();
        $block = $this->getLayout()->createBlock('Vnecoms\VendorsSms\Block\Vendors\Config\Form\Field\Mobile\Field');
        $block->setElement($element);

        return $block->toHtml();
    }
}
