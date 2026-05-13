<?php

namespace Vnecoms\VendorsCoupon\Block\Vendors\Coupon;

class Edit extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Container
{
    /** @var  \Magento\Framework\Registry */
    protected $_coreRegistry;

    /**
     * 
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
    }

    /**
     * Get edit form container banner text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __("Edit Coupon '%1'", $this->escapeHtml($this->_coreRegistry->registry('coupon')->getCode()));
    }


    /**
     * Call parent constructor
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_objectId = 'id';
        $this->_blockGroup = 'Vnecoms_VendorsCoupon'; //declare before controller
        $this->_controller = 'vendors_coupon';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Coupon'));
        $this->buttonList->update('delete', 'label' , __('Delete Coupon'));
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getRequest()->getParam('id')]);
    }

    /**
     * check action allowed
     *
     * @param $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
