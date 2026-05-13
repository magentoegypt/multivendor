<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Block\Import;

class Form extends \Vnecoms\Vendors\Block\Vendors\Widget\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Vnecoms_VendorsProductImportExport';
        $this->_controller = 'import';
        $this->_headerText = __('Import Form');
        
        parent::_construct();
        $this->addButton('back', [
            'id' => 'start_qwueue',
            'label' => __('Back'),
            'class' => 'bg-black btn-lg fa fa-angle-left',
            'button_class' => '',
            'onclick' => "setLocation('" . $this->getBackUrl() . "')",
        ]);
    }

    /**
     * Get Import URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('catalog/import/');
    }
}
