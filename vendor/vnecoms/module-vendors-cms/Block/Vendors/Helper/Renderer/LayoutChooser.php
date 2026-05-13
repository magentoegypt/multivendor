<?php

namespace Vnecoms\VendorsCms\Block\Vendors\Helper\Renderer;

class LayoutChooser extends \Magento\Framework\Data\Form\Element\Select
{
    public function getAfterElementHtml()
    {
        $html = '<div class="preview" id="layout-preview">';

        $html .= '</div>';

        return $html;
    }
}
