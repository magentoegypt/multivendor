<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsProduct\Model\Source\Approval;

class ProductApproval implements ObserverInterface
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
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    
    /**
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param \Magento\Email\Model\Template\Filter $filter
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Catalog\Model\Product $productFactory
     */
    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Email\Model\Template\Filter $filter,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ){
        $this->helper = $helper;
        $this->filter = $filter;
        $this->vendorFactory = $vendorFactory;
        $this->productFactory = $productFactory;
    }
    
    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $notificationType = $observer->getType();
        if($notificationType != 'product_approval') return;
        
        $additionalInfo = $observer->getAdditionalInfo();
        $productId = isset($additionalInfo['id'])?$additionalInfo['id']:'';
        if(!$productId) return;
        
        $product = $this->productFactory->create()->load($productId);
        if(!$product->getEntityId()) return;
        
        if(!($vendorId = $product->getVendorId())) return;
        $vendor = $this->vendorFactory->create()->load($vendorId);
        if(!$vendor->getEntityId()) return;
        
        $approval = $product->getData('approval');
        if(
            $approval == Approval::STATUS_APPROVED
        ) {
            /* Send vendor account approved sms message*/
            if($this->helper->canSendProductApprovedMessage($vendor->getId())){
                $message = $this->helper->getProductApprovedMessage();
                $this->filter->setVariables(['product' => $product]);
                $message = $this->filter->filter($message);
                $this->helper->sendSms($vendor, $message);
            }
        }elseif(
            $approval == Approval::STATUS_UNAPPROVED
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
