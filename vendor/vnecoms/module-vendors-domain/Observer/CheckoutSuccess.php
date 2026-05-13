<?php
namespace Vnecoms\VendorsDomain\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;

class CheckoutSuccess implements ObserverInterface
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
        $om = ObjectManager::getInstance();
        $action = $observer->getControllerAction();
        $request = $action->getRequest();
        $session = $om->get('Magento\Checkout\Model\Session');
        $vendorId = $session->getVendorId();
        $urlModel = $om->get('Vnecoms\VendorsDomain\Model\Url');
        if($vendorId){
            $vendorDomain = $om->get('Vnecoms\VendorsDomain\Model\Domain')->load($vendorId, 'vendor_id');
            if($vendorDomain->getId()){
                $domain = $vendorDomain->getDomain();
                
                $data = [
                    'last_success_quote_id' => $session->getLastSuccessQuoteId(),
                    'last_quote_id' => $session->getLastQuoteId(),
                    'last_order_id' => $session->getLastOrderId(),
                    'last_real_order_id' => $session->getLastRealOrderId(),
                ];
                $redirectUrl = $urlModel->getUrl('checkout/onepage/success',[
                    'transport' => base64_encode(serialize($data)),
                ]);
                $baseUrlInfo = parse_url($redirectUrl);
                $marketDomain = $baseUrlInfo['host'];
                $redirectUrl = str_replace($marketDomain, $domain, $redirectUrl);
                $action->getResponse()->setRedirect($redirectUrl);
                $action->getActionFlag()->set('', 'no-dispatch', true);
                $session->setVendorId(null);
            }
        }elseif($transport = $request->getParam('transport')){
            $transport = unserialize(base64_decode($transport));
            if(!isset($transport['last_quote_id'])) return;
            
            $lastSuccessQuoteId     = $transport['last_success_quote_id'];
            $lastQuoteId            = $transport['last_quote_id'];
            $lastOrderId            = $transport['last_order_id'];
            $lastRealOrderId        = $transport['last_real_order_id'];
        
            $session->setLastSuccessQuoteId($lastSuccessQuoteId);
            $session->setLastQuoteId($lastQuoteId);
            $session->setLastOrderId($lastOrderId);
            $session->setLastRealOrderId($lastRealOrderId);
            $redirectUrl = $urlModel->getUrl('checkout/onepage/success');
            $action->getResponse()->setRedirect($redirectUrl);
            $action->getActionFlag()->set('', 'no-dispatch', true);
        }
    }
}
