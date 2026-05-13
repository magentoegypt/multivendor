<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Config form fieldset renderer
 */
namespace Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Form;


class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Return header title part of html for fieldset
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        return '<a id="' .
            $element->getHtmlId() .
            '-head" href="#' .
            $element->getHtmlId() .
            '-link" onclick="Fieldset.toggleCollapse(\'' .
            $element->getHtmlId() .
            '\', \'\'); return false;">' . $element->getLegend() . '</a>';
    }
    /**
     * Collapsed or expanded fieldset when page loaded?
     *
     * @param AbstractElement $element
     * @return bool
     */
    protected function _isCollapseState($element)
    {
        return false;
    }

}
