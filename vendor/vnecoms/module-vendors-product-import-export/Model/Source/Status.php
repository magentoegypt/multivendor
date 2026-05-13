<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Model\Source;

use Vnecoms\VendorsProductImportExport\Model\Import\Data as ImportData;

class Status extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{


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
                ['label' => __('Draft'), 'value' => ImportData::STATUS_DRAFT],
                ['label' => __('In Process'), 'value' => ImportData::STATUS_IN_PROCESS],
                ['label' => __('Importing'), 'value' => ImportData::STATUS_IMPORTING],
                ['label' => __('Error'), 'value' => ImportData::STATUS_ERROR],
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
