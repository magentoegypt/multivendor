<?php

namespace Vnecoms\VendorsSms\Block\Adminhtml\System\Config\Form\Field;

class Credit extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $element->getElementHtml();
        $block = $this->getLayout()->createBlock('Vnecoms\VendorsSms\Block\Adminhtml\System\Config\Form\Field\Credit\Field');
        $block->setElement($element);

        return $block->toHtml();
    }
}
