<?php

namespace Vnecoms\Quotation\Model\Source;

class FormConfig extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const CONFIG_YES_REQUIRED   = 2;
    const CONFIG_YES            = 1;
    const CONFIG_NO             = 0;
    
    /**
     * Options array
     *
     * @var array
     */
    protected $_options = null;
    
    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions($blankOption = true)
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __("Yes and Required"), 'value' => self::CONFIG_YES_REQUIRED],
                ['label' => __("Yes"), 'value' => self::CONFIG_YES],
                ['label' => __("No"), 'value' => self::CONFIG_NO],
            ];
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray($blankOption = true)
    {
        $_options = [];
        foreach ($this->getAllOptions($blankOption) as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }
    
    
    /**
     * Get options as array
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
