<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCustomTheme\Model\Source;


class CustomTheme extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
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
    public function getAllOptions($blankOption = true)
    {
        if ($this->_options === null) {
            $this->_options = [];
            if($blankOption){
                $this->_options[] = ['label' => __("-- Please Select -- "), 'value' => ''];
            }
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $themeCollection = $om->create('Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Collection');
            foreach($themeCollection as $theme){
                $this->_options[] = ['label' => $theme->getTitle(), 'value' => $theme->getId()];
            }
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
