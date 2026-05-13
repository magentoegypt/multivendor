<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\RMA\Block\Adminhtml\Order;

class View extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
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

    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Prepare button and grid
     */
    protected function _prepareLayout()
    {
        $refeshButtonProps = [
            'id' => 'new',
            'label' => __('Create Request'),
            'class' => 'action-primary',
            'button_class' => 'primary scalable',
            'class_name' => 'Magento\Backend\Block\Widget\Button',
            'onclick' => "setLocation('" . $this->_getRmaNewUrl() . "')"
        ];
        $this->addButton('new', $refeshButtonProps);

        return parent::_prepareLayout();
    }
    /**
     * new rma image
     * @return mixed
     */
    protected function _getRmaNewUrl()
    {
        return $this->getUrl(
            'vrma/request/new',
            ["order_id"=>$this->getOrder()->getId()]
        );
    }
}
