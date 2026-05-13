<?php

namespace Vnecoms\BannerManager\Block\Vendors;

/**
 * Class Banner
 * 
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Banner extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * parent constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = "adminhtml_banner";
        $this->_blockGroup = 'Vnecoms_BannerManager';
        $this->_headerText = __('Banner');
        $this->_addButtonLabel = __('Add New Banner');
        parent::_construct();
    }

}

