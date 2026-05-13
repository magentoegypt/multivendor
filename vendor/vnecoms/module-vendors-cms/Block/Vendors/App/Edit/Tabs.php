<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Widget Instance edit tabs container.
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\Block\Vendors\App\Edit;

class Tabs extends \Vnecoms\Vendors\Block\Vendors\Widget\Tabs
{
    /**
     * Internal constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('app_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Frontend Application'));
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        if ($this->getRequest()->getParam('app_id')) {
            $this->addTabAfter('implementcode_section', [
                'label' => __('Implementation Code'),
                'title' => __('Implementation Code'),
                'content' => $this->getLayout()->createBlock('Vnecoms\VendorsCms\Block\Vendors\App\Edit\Tab\Implementcode')->setTemplate('Vnecoms_VendorsCms::app/implement_code.phtml')->toHtml(),
            ], 'properties_section');
        }

        return parent::_prepareLayout();
    }
}
