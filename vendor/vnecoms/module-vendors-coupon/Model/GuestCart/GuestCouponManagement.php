<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\GuestCart;

use Vnecoms\VendorsCoupon\Api\GuestCouponManagementInterface;
use Vnecoms\VendorsCoupon\Api\CouponManagementInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Coupon management class for guest carts.
 */
class GuestCouponManagement implements GuestCouponManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var CouponManagementInterface
     */
    private $couponManagement;

    /**
     * Constructs a coupon read service object.
     * 
     * @param CouponManagementInterface $couponManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CouponManagementInterface $couponManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->couponManagement = $couponManagement;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountDetail($cartId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->couponManagement->getDiscountDetail($quoteIdMask->getQuoteId());
    }
    
    /**
     * {@inheritdoc}
     */
    public function set($cartId, $couponCode)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->couponManagement->set($quoteIdMask->getQuoteId(), $couponCode);
    }

    /**
     * {@inheritdoc}
     */
    public function removeCoupon($cartId, $couponCode)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->couponManagement->removeCoupon($quoteIdMask->getQuoteId(), $couponCode);
    }
}
