<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Frontend\Customer\NewRequest;

use Magento\Sales\Model\Order\Item as OrderItem;

class DefaultItems extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Vnecoms\RMA\Model\Item
     */
    protected $_rmaItem;

    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_rmaHelper;

    /**
     * DefaultItems constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\RMA\Model\Item $rmaItem
     * @param \Vnecoms\RMA\Helper\Config $rmaHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\RMA\Model\Item $rmaItem,
        \Vnecoms\RMA\Helper\Config $rmaHelper,
        array $data = []
    ) {
        $this->_rmaHelper        = $rmaHelper;
        $this->_rmaItem   = $rmaItem;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getItem()->getOrder();
    }

    /**
     * @return array
     */
    public function getItemOptions()
    {
        $result = [];
        if ($options = $this->getItem()->getProductOptions()) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }

        return $result;
    }

    /**
     * @param string|array $value
     * @return string
     */
    public function getValueHtml($value)
    {
        if (is_array($value)) {
            return sprintf(
                '%d',
                $value['qty']
            ) . ' x ' . $this->escapeHtml(
                $value['title']
            ) . " " . $this->getItem()->getOrder()->formatPrice(
                $value['price']
            );
        } else {
            return $this->escapeHtml($value);
        }
    }

    /**
     * @param mixed $item
     * @return mixed
     */
    public function getSku($item)
    {
        if ($item->getProductOptionByCode('simple_sku')) {
            return $item->getProductOptionByCode('simple_sku');
        } else {
            return $item->getSku();
        }
    }

    /**
     * Return product additional information block
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function getProductAdditionalInformationBlock()
    {
        return $this->getLayout()->getBlock('additional.product.info');
    }

    /**
     * get Qty item
     * @return $qty
     */
    public function getRmaItem($item)
    {
        $rmaItems = $this->_rmaItem->getAllRmaByItemId($item->getId());

        if ($this->getOrder()->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE) {
            if ($item->getData("qty_shipped") == $item->getData("qty_invoiced")) {
                $qtyCheck = $item->getData("qty_shipped") - $item->getData("qty_refunded") - $rmaItems["qty"];
                $qty = $qtyCheck > 0 ? $qtyCheck : 0;
            } else {
                $qtyCheck = $item->getData("qty_invoiced") - $item->getData("qty_refunded") - $rmaItems["qty"];
                $qty = $qtyCheck > 0 ? $qtyCheck : 0;
            }
        } else {
            $qtyCheck = $item->getData("qty_invoiced") - $rmaItems["qty"];
            $qty = $qtyCheck > 0 ? $qtyCheck : 0;
        }
        $rmaItems["qty"] = $qty;
        return $rmaItems;
    }

    /**
     * is edit qty config rma
     */
    public function isEditQtyRma()
    {
        return $this->_rmaHelper->allowPerOrder();
    }

    /**
     * get View RMA URL
     */
    public function getViewOtherRmaUrl($requestId)
    {
        return $this->getUrl("vrma/customer/view", ["id"=>$requestId]);
    }

    /**
     * @param $item
     * @return mixed
     */
    public function getTrackingNumberByItem($item) {
        return $this->_rmaHelper->getTrackingNumberByOrderItem($item);
    }
}
