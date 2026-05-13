<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Vnecoms\Sms\Model\Sms;

class PrepareSmsData implements ObserverInterface
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
     * @var \Vnecoms\VendorsSms\Model\TransactionFactory
     */
    protected $smsTransactionFactory;
    
    /**
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param DateTime $date
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     */
    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        DateTime $date,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\VendorsSms\Model\TransactionFactory $smsTransactionFactory
    ){
        $this->helper = $helper;
        $this->date = $date;
        $this->vendorFactory = $vendorFactory;
        $this->smsTransactionFactory = $smsTransactionFactory;
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
        $smsData = $transport->getSmsData();
        
        /* Get vendor id from sms additional data*/
        if(!isset($smsData['additional_data']) || !$smsData['additional_data']) return;
        $additionalData = $smsData['additional_data'];
        $additionalData = explode("||", $additionalData);
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

        $smsData['vendor_id']   = $vendorId;
        $smsData['price']       = $price;
        /* The vendor id is applied so the vendor id in additional data is not needed any more.*/
        $additionalData = str_replace('vendor|'.$vendorId, '', $smsData['additional_data']);
        $smsData['additional_data'] = $additionalData?$additionalData:null;

        $transport->setSmsData($smsData);
        

        $smsStatus = isset($smsData['status'])?$smsData['status']:Sms::STATUS_FAILED;
        if(in_array($smsStatus, [Sms::STATUS_DELIVERED, Sms::STATUS_SENT, Sms::STATUS_PENDING])){
            $vendor = $this->vendorFactory->create()->load($vendorId);
            /*Substract SMS Credit.*/
            $vendor->setSmsCredit($vendor->getSmsCredit() - $price)->save();
            
            /* Save SMS credit transaction*/
            $transaction = $this->smsTransactionFactory->create();
            $transaction->setData([
                'vendor_id' => $vendor->getId(),
                'amount'    => -$price,
                'balance'   => $vendor->getSmsCredit(),
                'description' => __("A SMS is sent to you: \"%1\"", $smsData['message']),
            ])->save();
        }
        
        return $this;
    }
}
