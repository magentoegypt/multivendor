<?php
namespace MagentoEgypt\VendorExtend\Model;

use Vnecoms\VendorsApi\Model\OrderRepository as BaseOrderRepository;

class OrderRepository extends BaseOrderRepository
{
    /**
     * @param int $customerId
     * @param int $orderId
     * @return \MagentoEgypt\VendorExtend\Api\Data\Sale\OrderInterface
     */
    public function getOrder($customerId, $orderId){
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $collection = $this->vendorOrderCollectionFactory->create()
            ->addFieldToFilter('main_table.entity_id', $orderId);
        $collection->join(
            ['order_grid' => $collection->getTable('sales_order_grid')],
            'main_table.order_id=order_grid.entity_id',
            [
                'increment_id',
                'store_id',
                'store_name',
                'base_currency_code',
                'order_currency_code',
                'shipping_name',
                'billing_name',
                'shipping_and_handling',
                'total_refunded',
                'customer_name',
                'customer_email',
                'customer_group',
                'payment_method',
            ]
        );
        
        if(!$collection->count()) throw new LocalizedException(__('The order does not exist'));
        $vendorOrder = $collection->getFirstItem();

        if($vendorOrder->getVendorId() != $vendor->getId()) throw new LocalizedException(__('The order does not exist'));
        
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $result = $om->create('MagentoEgypt\VendorExtend\Api\Data\Sale\OrderInterface');
        $this->dataObjectHelper->populateWithArray(
            $result,
            $vendorOrder->getData(),
            'MagentoEgypt\VendorExtend\Api\Data\Sale\OrderInterface'
        );
        $this->addAdditionalInfoToOrderResult($result, $vendorOrder);
        return $result;
    }

    /**
     * @param \MagentoEgypt\VendorExtend\Api\Data\Sale\OrderInterface $orderResult
     * @param \Vnecoms\VendorsSales\Model\Order $vendorOrder
     */
    protected function addAdditionalInfoToOrderResult(
        \Vnecoms\VendorsApi\Api\Data\Sale\OrderInterface $orderResult,
        \Vnecoms\VendorsSales\Model\Order $vendorOrder
    ){
        $order = $vendorOrder->getOrder();
        $orderResult->setPayment($order->getPayment());
        $orderResult->setBillingAddress($order->getBillingAddress());
        $orderResult->setShippingAddress($order->getShippingAddress());
        $items = $vendorOrder->getAllItems();
        $orderItemIds = [];
        foreach($items as $item){
            $orderItemIds[] = $item->getId();
            $product = $this->productFactory->create()->load($item->getProductId());
            if(!$product->getId()) continue;
            $item->setThumbnail($product->getThumbnail());

            $extensionAttributes = $item->getExtensionAttributes();
            $extensionAttributes->setStatus($item->getStatus());
            $item->setExtensionAttributes($extensionAttributes);
        
            $options = $this->getItemOptions($item);
            if(sizeof($options)){
                $item->setItemOptions($options);
            }
        }

        $orderResult->setItems($items);
        $orderResult->setCanCancel($vendorOrder->canCancel());
        $orderResult->setCanShip($vendorOrder->canShip());
        $orderResult->setCanInvoice($vendorOrder->canInvoice());
        $orderResult->setCanCreditMemo($vendorOrder->canCreditMemo());
        $orderResult->setCommission($this->getCommission($orderItemIds));
    }

    /**
     * Get commission
     * @param array $orderItemIds
     * @return float
     */
    protected function getCommission($orderItemIds)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $invoiceItemIds = [];
        $invoiceItemCollection = $om->create('Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection');
        $invoiceItemCollection->addFieldToFilter('order_item_id', ['in' => $orderItemIds]);
        $amount = 0;
        foreach($invoiceItemCollection as $item){
            $amount += $item->getCommission();
        }
        $baseCommission = abs($amount);
        return $baseCommission;
    }
}