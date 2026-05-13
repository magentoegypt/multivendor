<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Adminhtml tax rule Edit Container
 */
namespace Vnecoms\VendorsShippingTableRate\Block\Vendors\Import;

class Edit extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Container
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Init class
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_controller = 'vendors_import';
        $this->_blockGroup = 'Vnecoms_VendorsShippingTableRate';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Import Rate'));
        $this->buttonList->remove("delete");


    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        if ($this->hasFormActionUrl()) {
            return $this->getData('form_action_url');
        }
        return $this->getUrl('*/*/saveImport');
    }

}
