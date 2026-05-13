<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsProduct\Model\Source\Approval;

class ShipmentSaveAfter implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsSms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Magento\Email\Model\Template\Filter
     */
    protected $filter;
    
    /**
     * Vendor Factory
     * 
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $vendorOrderFactory;
    
    /**
     * @var \Vnecoms\Sms\Model\ResourceModel\Sms\CollectionFactory
     */
    protected $smsCollectionFactory;
    
    /**
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param \Magento\Email\Model\Template\Filter $filter
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
     * @param \Vnecoms\Sms\Model\ResourceModel\Sms\CollectionFactory $smsCollectionFactory
     */
    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Email\Model\Template\Filter $filter,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory,
        \Vnecoms\Sms\Model\ResourceModel\Sms\CollectionFactory $smsCollectionFactory
    ){
        $this->helper = $helper;
        $this->filter = $filter;
        $this->vendorFactory = $vendorFactory;
        $this->vendorOrderFactory = $vendorOrderFactory;
        $this->smsCollectionFactory = $smsCollectionFactory;
    }
    
    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getShipment();
        /* 
         * Check if system sent a notification sms to this shipment already
         * The shipment object does not support for isObjectNew method so we have to do it like this
         */
        $additionalData = 'vendor_shipment|'.$shipment->getId();
        $collection = $this->smsCollectionFactory->create()
            ->addFieldToFilter('additional_data',['like' => '%'.$additionalData.'%']);
        if($collection->count()) return;
        
        if(!($vendorOrderId = $shipment->getVendorOrderId())) return;
        
        $vendorOrder = $this->vendorOrderFactory->create()->load($vendorOrderId);
        if(!$vendorOrder->getEntityId()) return;
        
        if(!($vendorId = $vendorOrder->getVendorId())) return;
        
        $vendor = $this->vendorFactory->create()->load($vendorId);
        if(!$vendor->getEntityId()) return;
        
        /* Send vendor account approved sms message*/
        if($this->helper->canSendNewShipmentMessage($vendor->getId())){
            $message = $this->helper->getNewShipmentMessage();
            $this->filter->setVariables(['shipment' => $shipment]);
            $message = $this->filter->filter($message);
            $this->helper->sendSms($vendor, $message,$additionalData);
        }
        
        return $this;
    }
}
