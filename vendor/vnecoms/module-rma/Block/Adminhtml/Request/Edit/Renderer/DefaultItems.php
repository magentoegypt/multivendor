<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Adminhtml\Request\Edit\Renderer;

use Magento\Sales\Model\Order\Item as OrderItem;

class DefaultItems extends \Magento\Framework\View\Element\Template
{


    /**
     * Retrieve current RMA model instance
     *
     * @return \Vnecoms\RMA\Model\Request
     */
    public function getRequestRma()
    {
        return $this->getItem()->getRma();
    }

    /**
     * get curent order from curent Item
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
        $rmaItems = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\RMA\Model\Item'
        )->getViewAllRmaByItemId($item->getId(), $this->getRequestRma()->getId());
        return $rmaItems;
    }

    /**
     * get Qty item
     * @return $item
     */
    public function getRmaItemExtant($item)
    {
        $rmaItems = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\RMA\Model\Item'
        )->getAllRmaByItemIdWithOutCurrentRequest($item->getId(), $this->getRequestRma()->getId());
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
     * get Qty item
     * @return $qty
     */
    public function checkItemForCurentRequest($item)
    {
        $rmaItems = $this->getRequestRma()->getAllItemFromRequest();
        foreach ($rmaItems as $itemObject) {
            if ($itemObject->getData("order_item_id") == $item->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * is edit qty config rma
     */
    public function isEditQtyRma()
    {
        $config = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\RMA\Helper\Config'
        )->allowPerOrder();
        return $config;
    }
    /**
     * get product image
     */
    public function getImage($product)
    {
        if ($product->getImage()) {
            $store = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            return $imageUrl;
        } else {
            $imageUrl = $this->getViewFileUrl('Magento_Catalog::images/product/placeholder/small_image.jpg');
        }
        return $imageUrl;
    }
    /**
     * check if request status pending
     * @param $request
     * @return bool
     */
    public function checkStatusRma()
    {
        $check = false;
        $status = $this->getRequestRma()->getStatusObject();
        if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_PENDING) {
            $check = true;
        }
        return $check;
    }
}
