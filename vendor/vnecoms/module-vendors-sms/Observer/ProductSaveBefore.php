<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsProduct\Model\Source\Approval;

class ProductSaveBefore implements ObserverInterface
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
        $product = $observer->getProduct();
        if(!$product->getEntityId()) return;
        
        if(!($vendorId = $product->getVendorId())) return;
        
        $vendor = $this->vendorFactory->create()->load($vendorId);
        if(!$vendor->getEntityId()) return;
        
        $approval = $product->getData('approval');
        $origApproval = $product->getOrigData('approval');
        if(
            $approval == Approval::STATUS_APPROVED &&
            $origApproval != Approval::STATUS_APPROVED
        ) {
            /* Send vendor account approved sms message*/
            if($this->helper->canSendProductApprovedMessage($vendor->getId())){
                $message = $this->helper->getProductApprovedMessage();
                $this->filter->setVariables(['product' => $product]);
                $message = $this->filter->filter($message);
                $this->helper->sendSms($vendor, $message);
            }
            
        }elseif(
            $approval == Approval::STATUS_UNAPPROVED &&
            $origApproval != Approval::STATUS_UNAPPROVED
        ) {
            /*Send vendor account unapproved sms message*/
            if($this->helper->canSendProductUnapprovedMessage($vendor->getId())){
                $message = $this->helper->getProductUnapprovedMessage();
                $this->filter->setVariables(['product' => $product]);
                $message = $this->filter->filter($message);
                $this->helper->sendSms($vendor, $message);
            }
        }
        
        return $this;
    }
}
