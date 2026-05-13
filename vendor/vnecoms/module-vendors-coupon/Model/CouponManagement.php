<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCoupon\Model;

use Vnecoms\VendorsCoupon\Api\CouponManagementInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Coupon management object.
 */
class CouponManagement extends \Magento\Quote\Model\CouponManagement implements CouponManagementInterface
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Vnecoms\VendorsCoupon\Model\CouponFactory
     */
    protected $_couponFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_date;

    /**
     * @var \Vnecoms\VendorsCoupon\Model\CouponDetailsFactory
     */
    protected $_couponDetailFactory;

    /**
     * Constructs a coupon read service object.
     *
     * @param \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Vnecoms\VendorsCoupon\Model\CouponDetailsFactory $couponDetailFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->_couponFactory = $couponFactory;
        $this->_date = $date;
        $this->_couponDetailFactory = $couponDetailFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountDetail($cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->collectTotals();
        return $quote->getData('vendor_discount_detail');
    }

    /**
     * {@inheritdoc}
     */
    public function set($cartId, $couponCode)
    {
        $coupon = trim($couponCode);
        $coupon = $this->_couponFactory->create()->load($coupon, 'code');

        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $couponDetail = $this->_couponDetailFactory->create();

        if(!$coupon->getId()) {
            return parent::set($cartId, $couponCode);
        }

        $today = $this->_date->date()->format('Y-m-d');

        if(
            ($coupon->getFromDate() && $today < $coupon->getFromDate()) ||
            ($coupon->getToDate() && $today > $coupon->getToDate()) ||
            ($coupon->getUsageLimit() > 0 && $coupon->getTimesUsed() >= $coupon->getUsageLimit())
        ) {
            return parent::set($cartId, $couponCode);
        }

        $canApplyCoupon = false;
        foreach($quote->getAllItems() as $item){
            if($item->getProduct()->getVendorId() == $coupon->getVendorId()){
                $canApplyCoupon = true;
                break;
            }
        }

        if(!$canApplyCoupon) return parent::set($cartId, $couponCode);



        try {
            $appliedCouponIds = $quote->getData('applied_vendor_coupon_ids');
            $appliedCouponIds = $appliedCouponIds?explode(',',$appliedCouponIds):[];

            if(!in_array($coupon->getId(), $appliedCouponIds)) $appliedCouponIds[] = $coupon->getId();

            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setData('applied_vendor_coupon_ids',implode(',', $appliedCouponIds))->collectTotals();
            $this->quoteRepository->save($quote);

            $afterAdd = explode(",", $quote->getData('applied_vendor_coupon_ids'));
            if (!in_array($coupon->getId(), $afterAdd)) {
                throw new \Exception(  __(
                      'The shopping cart is not eligible to use the coupon code "%1".',
                      $coupon->getCode()
                  ));
            }

            return true;

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not apply coupon code'));
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function removeCoupon($cartId, $couponCode)
    {
        $coupon = trim($couponCode);
        $coupon = $this->_couponFactory->create()->load($coupon, 'code');

        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $couponDetail = $this->_couponDetailFactory->create();

        $appliedCouponIds = $quote->getData('applied_vendor_coupon_ids');
        $appliedCouponIds = $appliedCouponIds?explode(',',$appliedCouponIds):[];

        if(($index = array_search($coupon->getId(), $appliedCouponIds)) !== false){
            array_splice($appliedCouponIds, $index, 1);
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setData('applied_vendor_coupon_ids',implode(',', $appliedCouponIds))->collectTotals();
            $this->quoteRepository->save($quote);
            return true;
        }

        return parent::remove($cartId);
    }
}
