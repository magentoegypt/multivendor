<?php

namespace Vnecoms\VendorsCustomTheme\Block\Vendors\Theme\Edit;

/**
 * @api
 * @since 100.0.2
 */
class Tabs extends \Vnecoms\Vendors\Block\Vendors\Widget\Tabs
{
    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('vendor_theme_info_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Theme Configuration'));
    }
}
