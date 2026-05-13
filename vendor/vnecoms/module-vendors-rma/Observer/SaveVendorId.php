<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveVendorId implements ObserverInterface
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
        $rma      = $observer->getRma();
        $items  = $rma->getData("order_item_id");
        if(!$items) return ;
        $vendorId = null;
        $vendorOrderId = null;
        foreach ($items as $item){
            $orderItem = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Sales\Model\Order\Item')->load($item["item_id"]);
            if($orderItem->getVendorId()){
				        $vendorId = $orderItem->getVendorId();
                $vendorOrderId = $orderItem->getVendorOrderId();
			      }
            break;
        }
        if($vendorId) $rma->setData("vendor_id",$vendorId);
        if($vendorOrderId) $rma->setData("vendor_order_id",$vendorOrderId);
    }
}
