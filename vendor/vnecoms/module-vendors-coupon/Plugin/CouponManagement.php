<?php
namespace Vnecoms\VendorsCoupon\Plugin;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class CouponManagement
{
    /**
     * @var \Vnecoms\VendorsCoupon\Model\CouponFactory
     */
    protected $_couponFactory;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_date;

    
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * 
     * @param unknown $couponFactory
     * @param unknown $date
     * @param unknown $quoteRepository
     */
    public function __construct(
        \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->_couponFactory = $couponFactory;
        $this->_date = $date;
        $this->quoteRepository = $quoteRepository;    
    }
    
    /**
     * Check if code exists in vendor coupon table
     *
     * @param string $code
     * @return bool
     */
    
    public function aroundSet(
        \Magento\Quote\Model\CouponManagement $subject,
        \Closure $proceed,
        $cartId, 
        $couponCode
    ) {
        $coupon = trim($couponCode);
        $coupon = $this->_couponFactory->create()->load($coupon, 'code');
        
        if(!$coupon->getId()) return $proceed($cartId, $couponCode);
        
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        
        $today = $this->_date->date()->format('Y-m-d');
        
        if(
            ($coupon->getFromDate() && $today < $coupon->getFromDate()) ||
            ($coupon->getToDate() && $today > $coupon->getToDate()) ||
            ($coupon->getUsageLimit() > 0 && $coupon->getTimesUsed() >= $coupon->getUsageLimit())
        ) {
            return $proceed($cartId, $couponCode);
        }
        
        $canApplyCoupon = false;
        foreach($quote->getAllItems() as $item){
            if($item->getProduct()->getVendorId() == $coupon->getVendorId()){
                $canApplyCoupon = true;
                break;
            }
        }
        
        if(!$canApplyCoupon) return $proceed($cartId, $couponCode);
        
       
                
        try {
            $appliedCouponIds = $quote->getData('applied_vendor_coupon_ids');
            $appliedCouponIds = $appliedCouponIds?explode(',',$appliedCouponIds):[];
            
            if(!in_array($coupon->getId(), $appliedCouponIds)) $appliedCouponIds[] = $coupon->getId();
            
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setData('applied_vendor_coupon_ids',implode(',', $appliedCouponIds))->collectTotals();
            $this->quoteRepository->save($quote);
            return true;

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not apply coupon code'));
        }

        return $proceed($cartId, $couponCode);;

    }
}
