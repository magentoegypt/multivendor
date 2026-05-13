<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 03/21/2016
 * Time: 09:10 AM
 */

namespace Vnecoms\VendorsRMA\Block\Vendor\Escalate;

use Vnecoms\Vendors\Block\Vendors\Widget\Form\Container;

class Edit extends Container
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
     * Department edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'escalate';
        $this->_blockGroup = 'Vnecoms_VendorsRMA';
        $this->_controller = 'vendor_escalate';
        parent::_construct();

        $request = $this->_coreRegistry->registry('current_request');

        $this->buttonList->update('save', 'label', __('Submit Escalate'));

    }

    /**
     * Get header with Department name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __("Escalate RMA #%1", $this->_coreRegistry->registry('current_request')->getIncrementId());
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/view',array("request_id"=>$this->_coreRegistry->registry('current_request')->getId()));
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
        return $this->getUrl('*/*/saveEscalate');
    }

}