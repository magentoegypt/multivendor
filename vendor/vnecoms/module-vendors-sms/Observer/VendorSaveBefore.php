<?php
namespace Vnecoms\VendorsSms\Observer;
use \Vnecoms\Vendors\Model\Vendor;
use Magento\Framework\Event\ObserverInterface;

class VendorSaveBefore implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsSms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Email\Model\Template\Filter
     */
    protected $filter;

    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Email\Model\Template\Filter $filter
    ){
        $this->helper = $helper;
        $this->filter = $filter;
    }

    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $vendor = $observer->getVendor();
        $status = $vendor->getData('status');
        if($vendor->isObjectNew()){
            /* Send notification message to admin if the vendor account need to be approved*/
            if($status == Vendor::STATUS_PENDING){
                if($this->helper->canSendToAdminPendingVendorMessage()){
                    $message = $this->helper->getAdminPendingVendorMessage();
                    $this->filter->setVariables(['vendor' => $vendor]);
                    $message = $this->filter->filter($message);
                    $this->helper->sendAdminSms($message);
                }
            }
            return;
        }


        $origStatus = $vendor->getOrigData('status');
        if(
            $status == Vendor::STATUS_APPROVED &&
            $origStatus != Vendor::STATUS_APPROVED
        ) {
            /* Send vendor account approved sms message*/
            if($this->helper->canSendVendorApprovedMessage()){
                $message = $this->helper->getVendorApprovedMessage();
                $this->filter->setVariables(['vendor' => $vendor]);
                $message = $this->filter->filter($message);
                $this->helper->sendSms($vendor, $message);
            }

        }elseif(
            $status == Vendor::STATUS_DISABLED &&
            $origStatus != Vendor::STATUS_DISABLED
        ) {
            /*Send vendor account unapproved sms message*/
            if($this->helper->canSendVendorUnapprovedMessage()){
                $message = $this->helper->getVendorUnapprovedMessage();
                $this->filter->setVariables(['vendor' => $vendor]);
                $message = $this->filter->filter($message);
                $this->helper->sendSms($vendor, $message);
            }
        }

        return $this;
    }
}
