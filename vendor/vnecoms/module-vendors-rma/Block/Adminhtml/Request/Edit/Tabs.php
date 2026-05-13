<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * description
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Request\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{

    /** @var string */
    protected $_template = 'Magento_Backend::widget/tabshoriz.phtml';

    protected $_coreRegistry;
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('request_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Request RMA'));
    }

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = [])
    {
        $this->_coreRegistry = $registry;
        return parent::__construct($context, $jsonEncoder, $authSession);
    }

    protected function _prepareLayout()
    {

        $this->addTab('adminhtml_rma_edit_tab_message', array(
            'label'     => __('RMA Comments'),
            'title'     => __('RMA Comments'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\VendorsRMA\Block\Adminhtml\Request\Edit\Tabs\Message')
                ->toHtml(),
            'active'    => true
        ));

        $this->addTab('adminhtml_rma_edit_tab_escalate', array(
            'label'     => __('RMA Escalate'),
            'title'     => __('RMA Escalate'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\VendorsRMA\Block\Adminhtml\Request\Edit\Tabs\Escalate')
                ->toHtml()
        ));

        $this->addTab('adminhtml_request_edit_tab_item', array(
            'label'     => __('RMA Items'),
            'title'     => __('RMA Items'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\RMA\Block\Adminhtml\Request\Edit\Tabs\Items')
                ->toHtml(),
        ));

        $this->addTab('adminhtml_request_edit_tab_address', array(
            'label'     => __('Customer Address'),
            'title'     => __('Customer Address'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\RMA\Block\Adminhtml\Request\Edit\Tabs\Address')
                ->toHtml(),
        ));

        $this->addTab('adminhtml_request_edit_tab_status', array(
            'label'     => __('RMA History'),
            'title'     => __('RMA History'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\RMA\Block\Adminhtml\Request\Edit\Tabs\Status')
                ->toHtml(),
        ));

        $this->addTab('adminhtml_request_edit_tab_history', array(
            'label'     => __('Other RMA'),
            'title'     => __('Other RMA'),
            'content'   => $this->getLayout()->createBlock('Vnecoms\RMA\Block\Adminhtml\Request\Edit\Tabs\History')
                ->toHtml(),
        ));

        return parent::_prepareLayout();
    }
}
