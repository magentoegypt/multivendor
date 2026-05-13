<?php
namespace Vnecoms\VendorsDomain\Observer;

use Magento\Framework\Event\ObserverInterface;

class RouterMatchVendor implements ObserverInterface
{    
    /**
     * Parse some special pages
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		$condition = $observer->getCondition();
		$requestPath = $condition->getRequestPath();
		
		$urlKeys = [
			'shipping-policies' => 'shippingPolicies',
			'refund-policies'   => 'refundPolicies',
			'about-us'          => 'aboutUs',
		];
		foreach($urlKeys as $request => $urlKey){
			$requestPath = str_replace($request, $urlKey, $requestPath);
		}

		$condition->setRequestPath($requestPath);
				
    }
}
