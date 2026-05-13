<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsReport\Model\Source;

class Period extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const PERIOD_HOUR = 'hour';
    const PERIOD_DAY = 'day';
    const PERIOD_WEEK = 'week';
    const PERIOD_MONTH = 'month';
    const PERIOD_QUARTER = 'quarter';
    const PERIOD_YEAR = 'year';

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
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Hour'), 'value' => self::PERIOD_HOUR],
                ['label' => __('Day'), 'value' => self::PERIOD_DAY],
                ['label' => __('Week'), 'value' => self::PERIOD_WEEK],
                ['label' => __('Month'), 'value' => self::PERIOD_MONTH],
                ['label' => __('Quarter'), 'value' => self::PERIOD_QUARTER],
                ['label' => __('Year'), 'value' => self::PERIOD_YEAR],
            ];
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->getAllOptions() as $option) {
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
