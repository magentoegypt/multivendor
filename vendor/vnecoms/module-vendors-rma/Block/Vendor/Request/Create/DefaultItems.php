<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Block\Vendor\Request\Create;

use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Sales Order Email items default renderer
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class DefaultItems extends \Magento\Framework\View\Element\Template
{


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
    public function getRmaItem($item) {
        $rmaItems = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\RMA\Model\Item'
        )->getAllRmaByItemId($item->getId());

        if( $this->getOrder()->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE) {
            if($item->getData("qty_shipped") == $item->getData("qty_invoiced")) {
                $qtyCheck = $item->getData("qty_shipped") - $item->getData("qty_refunded") - $rmaItems["qty"];
                $qty = $qtyCheck > 0 ? $qtyCheck : 0;
            }else{
                $qtyCheck = $item->getData("qty_invoiced") - $item->getData("qty_refunded") - $rmaItems["qty"];
                $qty = $qtyCheck > 0 ? $qtyCheck : 0;
            }
        }else{
            $qtyCheck = $item->getData("qty_invoiced") - $rmaItems["qty"];
            $qty = $qtyCheck > 0 ? $qtyCheck : 0;
        }
        $rmaItems["qty"] = $qty;
        return $rmaItems;
    }

    /**
     * is edit qty config rma
     */
    public function isEditQtyRma(){
        $config = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\RMA\Helper\Config')->allowPerOrder();
        return $config;
    }
}
