<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPage\Model\Source;

class ProfilePosition extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const POSITION_TOP = 'top';
    const POSITION_LEFT = 'left';
    const POSITION_RIGHT = 'right';

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
                ['label' => __("Top"), 'value' => self::POSITION_TOP],
                ['label' => __("Left"), 'value' => self::POSITION_LEFT],
                /* ['label' => __("Left"), 'value' => self::POSITION_RIGHT] */
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
