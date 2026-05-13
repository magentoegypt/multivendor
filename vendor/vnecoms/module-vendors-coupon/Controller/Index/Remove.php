<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Controller\Index;

class Remove extends \Magento\Checkout\Controller\Cart
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Coupon factory
     *
     * @var \Vnecoms\VendorsCoupon\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * 
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->couponFactory = $couponFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Initialize coupon
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $couponCode = trim($this->getRequest()->getParam('code'));
        $cartQuote = $this->cart->getQuote();

        $appliedCouponIds = explode(",", $cartQuote->getData('applied_vendor_coupon_ids'));
        
        try {
            $coupon = $this->couponFactory->create()->load($couponCode,'code');
            if(($index = array_search($coupon->getId(), $appliedCouponIds)) !== false){
                array_splice($appliedCouponIds, $index, 1);
                
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setData('applied_vendor_coupon_ids',implode(',', $appliedCouponIds))->collectTotals();
                $this->quoteRepository->save($cartQuote);
                $this->messageManager->addSuccess(__('You canceled the coupon code "%1".', $couponCode));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We cannot cancel the coupon code.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }

        return $this->_goBack();
    }
}
