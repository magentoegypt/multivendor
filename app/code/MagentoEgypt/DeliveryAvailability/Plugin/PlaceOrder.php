<?php
declare(strict_types=1);
namespace MagentoEgypt\DeliveryAvailability\Plugin;
class PlaceOrder
{
    public function __construct(private \Magento\Quote\Api\CartRepositoryInterface $quotes,
        private \MagentoEgypt\DeliveryAvailability\Model\Availability $availability) {}
    public function beforePlaceOrder(\Magento\Quote\Model\QuoteManagement $subject, $cartId, $paymentMethod=null): array
    {
        $quote=$this->quotes->getActive($cartId);
        if ($quote->isVirtual()) return [$cartId,$paymentMethod];
        $address=$quote->getShippingAddress(); $country=(string)$address->getCountryId();
        $items=array_filter($quote->getAllItems(),fn($item)=>!$item->getProduct()->isVirtual());
        $skus=array_map(fn($item)=>(string)$item->getSku(),$items);
        $rules=$this->availability->rules();
        if (!array_filter($rules,fn($r)=>$r['country']===$country && in_array($r['status'],['red','blacklist'],true)
            && ($r['sku']==='*' || in_array($r['sku'],$skus,true)))) return [$cartId,$paymentMethod];
        // Always use the checkout address, never browser/device state or stale IDs.
        $location=$this->availability->location($country,(int)$address->getRegionId(),0,0,(string)$address->getCity());
        foreach ($items as $item) {
            $check=$this->availability->check($location,(string)$item->getSku());
            if ($check['blocked']) throw new \Magento\Framework\Exception\LocalizedException(__('Delivery is unavailable or requires a quotation for %1 at this address. Please change the address or remove the item.', $item->getName()));
        }
        return [$cartId,$paymentMethod];
    }
}
