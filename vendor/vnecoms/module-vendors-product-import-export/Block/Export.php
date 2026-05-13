<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Block;

class Export extends \Vnecoms\Vendors\Block\Vendors\Widget\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Vnecoms_VendorsProductImportExport';
        $this->_controller = 'export';
        $this->_headerText = __('Export Products');
        $this->_addButtonLabel = __('Export Products');
        
        parent::_construct();
    }

    /**
     * Get Export URL
     *
     * @return string
     */
    public function getExportUrl()
    {
        return $this->getUrl('catalog/export/run');
    }
}
