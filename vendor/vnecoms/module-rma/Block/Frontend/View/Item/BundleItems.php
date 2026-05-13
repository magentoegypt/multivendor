<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Frontend\View\Item;

use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\App\ObjectManager;

/**
 * Order item render block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class BundleItems extends \Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer
{

    /**
     * @param mixed $item
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isShipmentSeparately($item = null)
    {
        if ($item) {
            if ($item->getOrderItem()) {
                $item = $item->getOrderItem();
            }
            $parentItem = $item->getParentItem();
            if ($parentItem) {
                $options = $parentItem->getProductOptions();
                if ($options) {
                    return (isset($options['shipment_type'])
                        && $options['shipment_type'] == AbstractType::SHIPMENT_SEPARATELY);
                }
            } else {
                $options = $item->getProductOptions();
                if ($options) {
                    return !(isset($options['shipment_type'])
                        && $options['shipment_type'] == AbstractType::SHIPMENT_SEPARATELY);
                }
            }
        }

        $options = $this->getOrderItem()->getProductOptions();
        if ($options) {
            if (isset($options['shipment_type']) && $options['shipment_type'] == AbstractType::SHIPMENT_SEPARATELY) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $item
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isChildCalculated($item = null)
    {
        if ($item) {
            if ($item->getOrderItem()) {
                $item = $item->getOrderItem();
            }
            $parentItem = $item->getParentItem();
            if ($parentItem) {
                $options = $parentItem->getProductOptions();
                if ($options) {
                    return (isset($options['product_calculations'])
                        && $options['product_calculations'] == AbstractType::CALCULATE_CHILD);
                }
            } else {
                $options = $item->getProductOptions();
                if ($options) {
                    return !(isset($options['product_calculations'])
                        && $options['product_calculations'] == AbstractType::CALCULATE_CHILD);
                }
            }
        }

        $options = $this->getOrderItem()->getProductOptions();
        if ($options) {
            if (isset($options['product_calculations'])
                && $options['product_calculations'] == AbstractType::CALCULATE_CHILD
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $item
     * @return mixed|null
     */
    public function getSelectionAttributes($item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Item) {
            $options = $item->getProductOptions();
        } else {
            $options = $item->getOrderItem()->getProductOptions();
        }
        if (isset($options['bundle_selection_attributes'])) {
            if(class_exists('Magento\Framework\Serialize\Serializer\Json')){
                $serializer = ObjectManager::getInstance()
                    ->create('Magento\Framework\Serialize\Serializer\Json');
                return $serializer->unserialize($options['bundle_selection_attributes']);
            }
            return unserialize($options['bundle_selection_attributes']);
        }
        return null;
    }

    /**
     * @param mixed $item
     * @return string
     */
    public function getValueHtml($item)
    {
        if ($attributes = $this->getSelectionAttributes($item)) {
            return sprintf('%d', $attributes['qty']) . ' x ' . $this->escapeHtml($item->getName()) . " "
            . $this->getOrder()->formatPrice($attributes['price']);
        } else {
            return $this->escapeHtml($item->getName());
        }
    }

    /**
     * Getting all available children for Invoice, Shipment or CreditMemo item
     *
     * @param \Magento\Framework\DataObject $item
     * @return array
     */
    public function getChildren($item)
    {
        $itemsArray = [];

        $items = null;
        if ($item instanceof \Magento\Sales\Model\Order\Invoice\Item) {
            $items = $item->getInvoice()->getAllItems();
        } elseif ($item instanceof \Magento\Sales\Model\Order\Shipment\Item) {
            $items = $item->getShipment()->getAllItems();
        } elseif ($item instanceof \Magento\Sales\Model\Order\Creditmemo\Item) {
            $items = $item->getCreditmemo()->getAllItems();
        }

        if ($items) {
            foreach ($items as $value) {
                $parentItem = $value->getOrderItem()->getParentItem();
                if ($parentItem) {
                    $itemsArray[$parentItem->getId()][$value->getOrderItemId()] = $value;
                } else {
                    $itemsArray[$value->getOrderItem()->getId()][$value->getOrderItemId()] = $value;
                }
            }
        }

        if (isset($itemsArray[$item->getOrderItem()->getId()])) {
            return $itemsArray[$item->getOrderItem()->getId()];
        } else {
            return null;
        }
    }

    /**
     * @param mixed $item
     * @return bool
     */
    public function canShowPriceInfo($item)
    {
        if ($item->getOrderItem()->getParentItem() && $this->isChildCalculated() ||
            !$item->getOrderItem()->getParentItem() && !$this->isChildCalculated()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get the html for item price
     *
     * @param OrderItem|InvoiceItem|CreditmemoItem $item
     * @return string
     */
    public function getItemPrice($item)
    {
        $block = $this->getLayout()->getBlock('item_price');
        $block->setItem($item);
        return $block->toHtml();
    }

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
     * get Qty item
     * @return $qty
     */
    public function checkItemForCurentRequest($items)
    {
        $newItems = [];
        $rmaItems = $this->getRequestRma()->getAllItemFromRequest();
        foreach ($rmaItems as $itemObject) {
            foreach ($items as $item) {
                if ($itemObject->getData("order_item_id") == $item->getId()) {
                    $newItems[] = $item->getId();
                }
            }
        }
        return $newItems;
    }

    /**
     * get Qty item
     * @return $qty

    public function checkItemForCurentRequest($item) {
    $rmaItems = $this->getRequestRma()->getAllItemFromRequest();
    foreach ($rmaItems as $itemObject){
    if($itemObject->getData("order_item_id") == $item->getId()) return true;
    }
    return false;
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
     * get product image
     */
    public function getImage($product)
    {

        if ($product && $product->getImage()) {
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
