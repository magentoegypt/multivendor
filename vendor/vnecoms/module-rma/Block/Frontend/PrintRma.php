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
namespace Vnecoms\RMA\Block\Frontend;

class PrintRma extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     *
     * @var \Magento\Sales\Model\Order\Item
     */
    protected $_itemFactory = null;


    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Order\ItemFactory $item,
        array $data = []
    ) {
        $this->_itemFactory = $item;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Prepare item before output
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $renderer
     * @return void
     */
    protected function _prepareItem(\Magento\Framework\View\Element\AbstractBlock $renderer)
    {
        $order = $this->getOrder();
        $renderer->getItem()->setOrder($order)->setRma($this->getRequestRma());
    }

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Print RMA # %1', $this->getRequestRma()->getIncrementId()));
    }



    /**
     * get Curent Request Data
     * @return mixed
     */
    public function getRequestRma()
    {
        return $this->_coreRegistry->registry("current_request");
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getRequestRma()->getOrderObject();
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order\Item
     */
    public function getObjectItem($itemId)
    {
        $item = $this->_itemFactory->create()->load($itemId);
        return $item;
    }

    /**
     * format date html
     * @param $date
     * @return mixed
     */
    public function getFormatDateHtml($date)
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        return $dateObj->date('F j, Y, g:i a', $date);
    }

    /**
     * get Status class
     * @return mixed
     */
    public function getStatusClass()
    {
        $class = "";
        switch ($this->getRequestRma()->getStatusObject()->getCode()) {
            case \Vnecoms\RMA\Model\Request::STATUS_PENDING:
                $class= "status status-pending";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_APPROVAL:
                $class= "status status-approval";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_PACKSENT:
                $class= "status status-package_sent";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_CANCELED:
                $class= "status status-canceled";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RECEIVED:
                $class= "status status-package_received";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RETURNED:
                $class= "status status-package_returned";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RESOLVED:
                $class= "status status-resolved";
                break;
        }
        return $class;
    }

    /**
     * get Type class
     * @return mixed
     */
    public function getTypeClass()
    {
        if ($this->getRequestRma()->getType() == "replace") {
            return "type type-replace";
        }
        return "type type-refund";
    }
}
