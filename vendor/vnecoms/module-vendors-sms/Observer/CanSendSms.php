<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Vnecoms\Sms\Model\Sms;
use Vnecoms\VendorsSms\Model\CreditProcessor\Sms as SmsProcessor;

class CanSendSms implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsSms\Helper\Data
     */
    protected $helper;
    
    /**
     * Vendor Factory
     * 
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    
    /**
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param DateTime $date
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     */
    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        DateTime $date,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
    ){
        $this->helper = $helper;
        $this->date = $date;
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
        $transport = $observer->getTransport();

        if (!$transport->getAdditionalData()) return $this;

        $additionalData = explode("||", $transport->getAdditionalData());

        $vendorId = false;
        foreach($additionalData as $addData){
            if(!$addData) continue;
            $addData = explode("|", $addData);
            if($addData[0] == 'vendor'){
                $vendorId = $addData[1];
                break;
            }
        }
        
        if(!$vendorId) return;
        $vendor = $this->vendorFactory->create()->load($vendorId);
        /*Get SMS count of current month*/
        $smsCount = $this->helper->countVendorSms($vendorId, $this->date->date('Y-m-1'));
        $tierPrice = $this->helper->getSmsTierPrice();
        $price = 0;
        foreach($tierPrice as $tier => $tierData){
            if($smsCount < $tier){
                $price = isset($tierData['price'])?$tierData['price']:0;
                break;
            }
        }

        $canSendSms = $vendor->getSmsCredit() >= $price;
        $transport->setData('can_send_sms', $canSendSms);
        if(!$canSendSms) $transport->setStatus(Sms::STATUS_NOT_ENOUGH_CREDIT);
        return $this;
    }
}
