<?php
namespace Vnecoms\VendorsCoupon\Model\Quote;

class Discount extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
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
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Vnecoms\VendorsCoupon\Model\Coupon\Action\CalculatorFactory
     */
    private $calculatorFactory;

    /**
     *
     * @param \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
     * @param \Vnecoms\VendorsCoupon\Model\Coupon\Action\CalculatorFactory $calculatorFactory
     */
    public function __construct(
        \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Vnecoms\VendorsCoupon\Model\Coupon\Action\CalculatorFactory $calculatorFactory
    ) {
        $this->setCode('vendorcoupon');
        $this->calculatorFactory = $calculatorFactory;
        $this->_couponFactory = $couponFactory;
        $this->_date = $date;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Collect address discount amount
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $appliedRules = $quote->getData('applied_vendor_coupon_ids');
        $address = $shippingAssignment->getShipping()->getAddress();

        $items = $shippingAssignment->getItems();
        if (!count($items) || !$appliedRules) {
            return $this;
        }

        /*Group items by vendor id*/
        $vendorItems = [];
        $itemsTotal = [];
        foreach($items as $item){
            if(!($vendorId = $item->getProduct()->getVendorId())) continue;
            if(!isset($vendorItems[$vendorId])){
                $vendorItems[$vendorId] = [];
            }
            $vendorItems[$vendorId][] = $item;
            if(!isset($itemsTotal[$vendorId])){
                $itemsTotal[$vendorId] = 0;
            }
            if ($item->getParentItem()) {
                continue;
            }
            if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                foreach ($item->getChildren() as $child) {
                    $itemsTotal[$vendorId] += $child->getBaseRowTotal() - $child->getBaseDiscountAmount();
                }
            } else {
                $itemsTotal[$vendorId] += $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
            }
        }

        $store = $quote->getStore();
        $appliedRules = explode(",", $appliedRules);
        $validRules = [];
        $discountAmount = [];
        $discountDetail = [];
        foreach($appliedRules as $couponId){
            $coupon = $this->_couponFactory->create()->load($couponId);
            $vendorId = $coupon->getVendorId();
            if(!isset($vendorItems[$vendorId])) continue;

            $today = $this->_date->date()->format('Y-m-d');
            if(
                ($coupon->getFromDate() && $today < $coupon->getFromDate()) ||
                ($coupon->getToDate() && $today > $coupon->getToDate()) ||
                ($coupon->getUsageLimit() > 0 && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) ||
                ($itemsTotal[$vendorId] <= 0)
            ) {
                continue;
            }
            $maxDiscount = $itemsTotal[$vendorId];
            $discount = $this->getDiscountData($coupon, $maxDiscount, $address);
            if ($discount) {
              $canApplyCoupon = false;
              $validRules[$vendorId] = $couponId;
              $rate = $discount/$itemsTotal[$vendorId];
              foreach($vendorItems[$vendorId] as $item){
                  if ($item->getParentItem()) {
                      continue;
                  }

                  if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                      foreach ($item->getChildren() as $child) {
                          $itemAmount = $child->getBaseRowTotal() - $child->getBaseDiscountAmount();
                          $itemDiscount = $itemAmount * $rate;

                          $child->setDiscountAmount($child->getDiscountAmount() + $this->priceCurrency->convert($itemDiscount, $store));
                          $child->setBaseDiscountAmount($child->getBaseDiscountAmount() + $itemDiscount);
                      }
                  } else {
                      $itemAmount = $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
                      $itemDiscount = $itemAmount * $rate;
                      $item->setDiscountAmount($item->getDiscountAmount() + $this->priceCurrency->convert($itemDiscount, $store));
                      $item->setBaseDiscountAmount($item->getBaseDiscountAmount() + $itemDiscount);
                  }

              }
              $discountDetail[$vendorId] = [
                  'label' => $coupon->getCode(),
                  'amount' => -(float)$this->priceCurrency->convert($discount, $store),
                  'vendor_id' => $coupon->getVendorId(),
              ];
              $discountAmount[$vendorId]= $discount;
            }
        }
        $discountAmount = array_sum($discountAmount);
        $discountDetail = array_values($discountDetail);

        if($total->getDiscountAmount() != 0){
            array_unshift($discountDetail, [
                'label' => $total->getDiscountDescription(),
                'amount' => (float)$total->getDiscountAmount(),
            ]);
        }

        $couponAmmount = $this->priceCurrency->convert($discountAmount, $store);

        $total->addTotalAmount('discount', -$couponAmmount);
        $total->addBaseTotalAmount('discount', -$discountAmount);

        $quote->setData('applied_vendor_coupon_ids',implode(',', $validRules));
        $quote->setData('vendor_discount_detail', json_encode($discountDetail));

    }

    /**
     * Get discount Data
     *
     * @param $coupon
     * @param $maxDiscount
     * @param $address
     */
    protected function getDiscountData($coupon, $maxDiscount, $address)
    {
        $discountCalculator = $this->calculatorFactory->create($coupon->getAction());
        $discountData = $discountCalculator->calculate($coupon, $maxDiscount, $address);
        return $discountData;
    }

    /**
     * Add discount total information to address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        return $result;
    }
}
