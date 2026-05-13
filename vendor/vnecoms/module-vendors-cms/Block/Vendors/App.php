<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

/**
 * WYSIWYG widget plugin main block.
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\Block\Vendors;

class App extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Block constructor.
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Vnecoms_VendorsCms';
        $this->_controller = 'vendors_app';
       // $this->_mode = 'edit';
        $this->_headerText = __('Manage Frontend Applications');
        parent::_construct();
        $this->buttonList->update('add', 'label', __('Add Application'));
    }
}
