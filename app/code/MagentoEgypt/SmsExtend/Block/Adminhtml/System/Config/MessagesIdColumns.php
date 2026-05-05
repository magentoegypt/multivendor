<?php
/**
 * Copyright © Magento. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace MagentoEgypt\SmsExtend\Block\Adminhtml\System\Config;

/**
 * Class with class map capability
 *
 * ...
 */
class MessagesIdColumns extends \Magento\Framework\View\Element\Template
{
    protected $_renderer;

    protected $_type;

    /**
     * Render HTML
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _toHtml()
    {
        $html = '<input type="text" class="input-text admin__control-text" id="' .$this->getInputId(). '" name="' .$this->getName(). '" '
        . $this->serialize($this->getHtmlAttributes()) . ' value="';
        $html .= ($this->getValue() ?: "");
        $html .= '" />';
        return $html;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Return the attributes for Html.
     *
     * @return string[]
     */
    public function getHtmlAttributes()
    {
        return [
            'title',
            'class',
            'style',
            'onclick',
            'onchange',
            'rows',
            'cols',
            'readonly',
            'disabled',
            'onkeyup',
            'tabindex',
            'data-form-part',
            'data-role',
            'data-action'
        ];
    }

    /**
     * @param string $value
     * @return \Vnecoms\Sms\Block\Adminhtml\System\Config\MessagesColumns
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}