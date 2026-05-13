<?php
namespace Vnecoms\VendorsDomain\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;

class Checkout implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsDomain\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    
    /**
     * @param \Vnecoms\VendorsDomain\Helper\Data $helper
     * @param \Vnecoms\VendorsDomain\Model\DomainFactory $domainFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Vnecoms\VendorsDomain\Helper\Data $helper,
        \Magento\Framework\Registry $registry
    ){
        $this->helper = $helper;
        $this->coreRegistry = $registry;
    }
    
    /**
     * Add layout handle for vendor domain page.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->helper->isRedirectToMainMarketplace()) return;
        $om         = ObjectManager::getInstance();
        $action     = $observer->getControllerAction();
        $request    = $action->getRequest();
        $urlModel   = $om->get('Vnecoms\VendorsDomain\Model\Url');
        $checkoutSession    = $om->get('Magento\Checkout\Model\Session');
        if($transport = $request->getParam('transport')){
            $transport = unserialize(base64_decode($transport));
            if(!isset($transport['quote_id'])) return;
            $customerId = isset($transport['customer_id'])?$transport['customer_id']:'';
            $quoteId = isset($transport['quote_id'])?$transport['quote_id']:'';
            $vendorId = isset($transport['vendor_id'])?$transport['vendor_id']:'';
            $om->get('Magento\Customer\Model\Session')->logout()->clearStorage()->loginById($customerId);
            $checkoutSession->clearStorage()->setQuoteId($quoteId);
            $checkoutSession->setVendorId($vendorId);
            $redirectUrl = $urlModel->getUrl('checkout',[
                'quote_id' => $quoteId,
                'vendor_id' => $vendorId,
            ]);
            
            $action->getResponse()->setRedirect($redirectUrl);
            $action->getActionFlag()->set('', 'no-dispatch', true);
        }elseif($quoteId = $request->getParam('quote_id')){
            $vendorId = $request->getParam('vendor_id');
            $checkoutSession->clearStorage()->setQuoteId($quoteId);
            $checkoutSession->setVendorId($vendorId);
        }else{
            $currentBaseUrl = trim($urlModel->getBaseUrl(),'/');
            $marketbaseUrl = trim($urlModel->getBaseUrl(['foce_use_marketplace_domain' => true]),'/');
            if($currentBaseUrl != $marketbaseUrl){
                $data = [
                    'quote_id'      => $checkoutSession->getQuoteId(),
                    'customer_id'   => $om->get('Magento\Customer\Model\Session')->getCustomerId(),
                    'vendor_id'     => $this->coreRegistry->registry('vendor_id')
                ];
                $redirectUrl = $urlModel->getUrl('checkout',[
                    'foce_use_marketplace_domain' => true,
                    'transport' => base64_encode(serialize($data)),
                ]);
                
                $action->getResponse()->setRedirect($redirectUrl);
                $action->getActionFlag()->set('', 'no-dispatch', true);
            }
        }
    }
}
