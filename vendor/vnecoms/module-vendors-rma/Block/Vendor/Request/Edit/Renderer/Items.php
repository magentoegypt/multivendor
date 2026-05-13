<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Vnecoms\VendorsRMA\Block\Vendor\Request\Edit\Renderer;

class Items extends \Magento\Sales\Block\Items\AbstractItems
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
        $renderer->getItem()->setOrder($order)->setRma($this->getRmaData());
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getRmaData()->getOrderObject();
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order\Item
     */
    public function getObjectItem($itemId){
        $item = $this->_itemFactory->create()->load($itemId);
        return $item;
    }
}
