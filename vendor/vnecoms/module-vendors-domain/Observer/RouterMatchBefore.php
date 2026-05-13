<?php
namespace Vnecoms\VendorsDomain\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsDomain\Model\Config\Source\DomainServerParams;

class RouterMatchBefore implements ObserverInterface
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
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $pageHelper;
    
    /**
     * @param \Vnecoms\VendorsDomain\Helper\Data $helper
     * @param \Vnecoms\VendorsPage\Helper\Data $pageHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Vnecoms\VendorsDomain\Helper\Data $helper,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Magento\Framework\Registry $registry
    ){
        $this->helper = $helper;
        $this->coreRegistry = $registry;
        $this->pageHelper = $pageHelper;
    }
    
    /**
     * Point vendor domain to vendor page.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->coreRegistry->registry('vnecoms_vendorsdomain_processed')){
            $this->coreRegistry->register('vnecoms_vendorsdomain_processed', true);
            $router = $observer->getRouter();
            $request = $observer->getRequest();
            $condition = $observer->getCondition();
            
            $domainParam = $this->helper->getCurrentDomainParam();
            if($domainParam == DomainServerParams::CUSTOM_PARAM){
                $domainParam = $this->helper->getCustomDomainParam();
            }
            $currentDomain = $request->getServer($domainParam);
            $domain = $this->helper->getDomainObject($currentDomain);
            
            if(!$domain) return $this;
            $isSecure = $domain->getIsSecure();
            $isCurrentlySecured = $this->helper->isCurrentlySecuredRequest($request);
            
            if(
                strtolower($domain->getDomain()) != strtolower($currentDomain) ||
                $isSecure != $isCurrentlySecured
            ){
				$scheme = $isSecure?'https://':'http://';
                $url = $scheme.$domain->getDomain().$request->getRequestUri();
                $condition->setRedirectUrl($url);
                return;
            }
            
            $this->coreRegistry->register('vnecoms_is_vendor_domain', true);
            if (!$this->coreRegistry->registry('vendor_id')) {
                $this->coreRegistry->register('vendor_id', $domain->getVendorId());
                $this->coreRegistry->register('vendor', $domain->getVendor());
                $this->coreRegistry->register('current_vendor', $domain->getVendor());
            }
            
            $pathInfo = $this->pageHelper->getUrlKey()?
                    '/'.$this->pageHelper->getUrlKey().'/'.$domain->getVendor()->getVendorId():
                    $domain->getVendor()->getVendorId();
            
            
            $urlKey = $this->helper->getUrlKey();
            $oldPathInfo = trim($request->getPathInfo(),'/');
            $alias = $oldPathInfo;
            if(
                !$oldPathInfo ||
                substr($oldPathInfo, 0, strlen($urlKey)) == $urlKey
            ){
                $oldPathInfo = trim(str_replace($urlKey, '',$oldPathInfo), '/');
                $urlKeys = [
                    'shipping-policies' => 'shippingPolicies',
                    'refund-policies'   => 'refundPolicies',
                    'about-us'          => 'aboutUs',
                ];
                foreach($urlKeys as $request => $urlKey){
                    $oldPathInfo = str_replace($request, $urlKey, $oldPathInfo);
                }
                $identifier = trim($pathInfo.'/'.$oldPathInfo, '/');
                $condition->setIdentifier($identifier);
            }
            $condition->setAlias($alias);
        }else{
			$condition = $observer->getCondition();
			$identifier = $condition->getIdentifier();
			$urlKeys = [
				'shipping-policies' => 'shippingPolicies',
				'refund-policies'   => 'refundPolicies',
				'about-us'          => 'aboutUs',
			];
			foreach($urlKeys as $request => $urlKey){
				$identifier = str_replace($request, $urlKey, $identifier);
			}
			$condition->setIdentifier($identifier);
		}
        return $this;
    }
}
