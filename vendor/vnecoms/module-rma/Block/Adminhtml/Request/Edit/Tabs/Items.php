<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Sales Order Email order items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\RMA\Block\Adminhtml\Request\Edit\Tabs;

use Magento\Framework\Registry;

class Items extends \Magento\Backend\Block\Template
{

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $coreRegistry,
        array $data = []
    ) {
    
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
    }


    public function _toHtml()
    {
        parent::_toHtml();
        $html =  $this->getLayout()->getBlock("rma_items")->setRmaData($this->getRequestData())->toHtml();
        return $html;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Vnecoms\RMA\Model\Request
     */
    public function getRequestData()
    {
        return $this->_coreRegistry->registry('current_request');
    }
}
