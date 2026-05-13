<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCustomTheme\Model\Source;


class BaseTheme extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
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
            $this->_options = [];
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $themeCollection = $om->create('Magento\Theme\Model\ResourceModel\Theme\Collection');
            foreach($themeCollection as $theme){
                $this->_options[] = ['label' => $theme->getThemeTitle(), 'value' => $theme->getId()];
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
        if($blankOption){
            $_options[''] = __("-- Please Select -- ");
        }
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
