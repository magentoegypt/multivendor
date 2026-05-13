<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Block\Account\Create\Fieldset;

/**
 * Customer date of birth attribute block
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DateTime extends Date
{
    /**
     * Create correct date field
     *
     * @return string
     */
    public function getFieldHtml()
    {
        $this->dateElement->setData(
            [
                'extra_params' => $this->getHtmlExtraParams(),
                'name' => $this->getFieldName(),
                'id' => $this->getFieldId(),
                'class' => $this->getFrontendClass(),
                'value' => $this->getFieldValue(),
                'date_format' => $this->getDateFormat(),
                'time_format' => $this->getTimeFormat(),
                'image' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
                'years_range' => '-120y:c+nn',
                'change_month' => 'true',
                'change_year' => 'true',
                'show_on' => 'both',
                'first_day' => $this->getFirstDay()
            ]
        );
        return $this->dateElement->getHtml();
    }

    /**
     * Returns format which will be applied for DOB in javascript
     *
     * @return string
     */
    public function getTimeFormat()
    {
        return 'hh:mm:ss';
    }

    /**
     * Return data-validate rules
     *
     * @return string
     */
    public function getHtmlExtraParams()
    {
        $validators = [];
        if ($this->isAttributeRequired()) {
            $validators['required'] = true;
        }
        $validators['validate-date'] = [
            'dateFormat' => $this->getDateFormat()." ".$this->getTimeFormat(),
        ];
        return 'data-validate="' . $this->_escaper->escapeHtml(json_encode($validators)) . '"';
    }
}
