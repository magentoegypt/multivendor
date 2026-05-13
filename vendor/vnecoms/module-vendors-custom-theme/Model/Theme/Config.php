<?php
namespace Vnecoms\VendorsCustomTheme\Model\Theme;

class Config extends \Magento\Framework\Model\AbstractModel
{    
    const ENTITY = 'vendor_theme_config';
    
    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vendor_theme_config';
    
    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'vendor_theme_config';
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config');
    }
}
