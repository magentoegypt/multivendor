<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsProduct\Model\Source\Approval;

class OrderSaveAfter implements ObserverInterface
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
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param \Magento\Email\Model\Template\Filter $filter
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     */
    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Email\Model\Template\Filter $filter,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
    ){
        $this->helper = $helper;
        $this->filter = $filter;
        $this->vendorFactory = $vendorFactory;
    }
    
    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $vendorOrder = $observer->getVendorOrder();
        if(!$vendorOrder->isObjectNew()) return;
        
        if(!($vendorId = $vendorOrder->getVendorId())) return;
        
        $vendor = $this->vendorFactory->create()->load($vendorId);
        if(!$vendor->getEntityId()) return;
        
        /* Send vendor account approved sms message*/
        if($this->helper->canSendNewOrderMessage($vendor->getId())){
            $vendorOrder->setIncrementId($vendorOrder->getOrder()->getIncrementId());
            $message = $this->helper->getNewOrderMessage();
            $this->filter->setVariables(['order' => $vendorOrder]);
            $message = $this->filter->filter($message);
            $this->helper->sendSms($vendor, $message);
        }
        
        return $this;
    }
}
