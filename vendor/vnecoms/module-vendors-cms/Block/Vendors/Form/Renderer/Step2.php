<?php

namespace Vnecoms\VendorsCms\Block\Vendors\Form\Renderer;

class Step2 extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Renderer\Fieldset\Element implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    protected $_template = 'Vnecoms_VendorsCms::form/renderer/fieldset/step2.phtml';

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return parent::render($element);
    }
}
