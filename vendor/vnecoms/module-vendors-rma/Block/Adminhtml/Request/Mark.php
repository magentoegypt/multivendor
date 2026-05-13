<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog rule edit form block
 */
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Request;

class Mark extends \Magento\Backend\Block\Widget\Form\Container
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
        \Vnecoms\RMA\Helper\Data $requestData,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Apply" button
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_mode = 'mark';
        $this->_objectId = 'id';
        $this->_blockGroup = 'Vnecoms_VendorsRMA';
        $this->_controller = 'adminhtml_request';

        parent::_construct();


        $request = $this->_coreRegistry->registry('current_request');

        $this->buttonList->update('save', 'label', __('Send'));
        $this->buttonList->remove("reset");
        $this->buttonList->remove("delete");
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText() {
        $request = $this->_coreRegistry->registry('current_request');
        if ($request->getId()) {
            return __("Mark as Resolve for RMA #'%1'", $this->escapeHtml($request->getId()));
        } else {
            return __('Mark as Resolve');
        }
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
        return $this->getUrl('*/*/send');
    }
}
