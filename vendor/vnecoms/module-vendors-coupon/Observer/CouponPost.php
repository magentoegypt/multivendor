<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Cart as CustomerCart;

class CouponPost implements ObserverInterface
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
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $_redirect;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     *
     * @param \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
     * @param CustomerCart $cart
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        CustomerCart $cart,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_couponFactory = $couponFactory;
        $this->_date = $date;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->_redirect = $redirect;
        $this->messageManager = $messageManager;

    }

    /**
     *
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\ActionInterface */
        $controllerAction = $observer->getControllerAction();
        $coupon = trim($controllerAction->getRequest()->getParam('coupon_code'));
        $coupon = $this->_couponFactory->create()->load($coupon, 'code');

        if(!$coupon->getId()) return;

        $cartQuote = $this->cart->getQuote();
        /* There is no items in cart.*/
        if(!$cartQuote->getItemsCount()) return;

        $today = $this->_date->date()->format('Y-m-d');

        if(
            ($coupon->getFromDate() && $today < $coupon->getFromDate()) ||
            ($coupon->getToDate() && $today > $coupon->getToDate()) ||
            ($coupon->getUsageLimit() > 0 && $coupon->getTimesUsed() >= $coupon->getUsageLimit())
        ) {
            return;
        }

        $canApplyCoupon = false;
        foreach($cartQuote->getAllItems() as $item){
            if($item->getProduct()->getVendorId() == $coupon->getVendorId()){
                $canApplyCoupon = true;
                break;
            }
        }

        if(!$canApplyCoupon) return;

        $appliedCouponIds = $cartQuote->getData('applied_vendor_coupon_ids');
        $appliedCouponIds = $appliedCouponIds?explode(',',$appliedCouponIds):[];

        if(!in_array($coupon->getId(), $appliedCouponIds)) $appliedCouponIds[] = $coupon->getId();

        $cartQuote->getShippingAddress()->setCollectShippingRates(true);
        $cartQuote->setData('applied_vendor_coupon_ids',implode(',', $appliedCouponIds))->setTotalsCollectedFlag(false)->collectTotals();
        $this->quoteRepository->save($cartQuote);

        $afterAdd = explode(",", $cartQuote->getData('applied_vendor_coupon_ids'));
        if (in_array($coupon->getId(), $afterAdd)) {
          $this->messageManager->addSuccess(__('You used coupon code "%1".', $coupon->getCode()));
        } else {
          $this->messageManager->addErrorMessage(
              __(
                  'The shopping cart is not eligible to use the coupon code "%1".',
                  $coupon->getCode()
              )
          );
        }

        $this->_redirect->redirect($controllerAction->getResponse(), 'checkout/cart');
        $controllerAction->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
    }
}
