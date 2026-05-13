<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Observer;

use Magento\Framework\Event\ObserverInterface;

class RequestValidateItem implements ObserverInterface
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;


    /**
     * PendingRmaObserver constructor.
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param \Magento\Framework\View\Element\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        array $data = []
    ) {
        $this->_urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }


    /**
     * Add the notification if there are any vendor awaiting for approval.
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport      = $observer->getTransport();
        $request = $observer->getRequest();
        $errors =  $transport->getErrors();
        $items  = $transport->getItems();
        $checkGroupVendorItem = $this->getItemGroupVendor($items);
  
        if(count($checkGroupVendorItem) > 1){
            $errors[] = __('You can not add product for more than 1 vendor.');
        }
        if($request->getData("refund_amount_type") == "custom_amount" && $request->getType() == "refund"){
            $maxAmount = 0;
            foreach ($checkGroupVendorItem as $checkGroupItem){
                foreach ($checkGroupItem as $item){
                    $maxAmount += $item["amount"];
                }
            }

            if($request->getData("refund_custom_amount") > $maxAmount){
                $errors[] = __('Custom amount is not valid.');
            }
        }
        $transport->setErrors($errors);
    }

    /**
     * get Group Vendor Item
     * @return array
     */
    public function getItemGroupVendor($_items){
        $groupVendor = [];
        foreach($_items as $item) {
            $orderItem = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Sales\Model\Order\Item')->load($item["item_id"]);
			$vendorId = $orderItem->getVendorId();
			if($vendorId){
                /*Get item by vendor id*/
                if(!isset($groupVendor[$vendorId])) $groupVendor[$vendorId] = array();

                $item["amount"] = (($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount())
                        /$orderItem->getQtyOrdered())*$item["item_qty"];

                $groupVendor[$vendorId][] = $item;
            } else {
                $item["amount"] = (($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount())
                        /$orderItem->getQtyOrdered())*$item["item_qty"];
                $groupVendor['no_vendor'][] = $item;
            }
        }
        return $groupVendor;
    }



}
