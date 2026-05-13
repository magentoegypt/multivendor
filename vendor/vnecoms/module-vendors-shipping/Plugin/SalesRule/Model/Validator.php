<?php

namespace Vnecoms\VendorsShipping\Plugin\SalesRule\Model;

use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Helper\CartFixedDiscount;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * @inheritdoc
 */
class Validator
{
    /**
     * Rule source collection
     *
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    protected $_rules;

    /**
     * @var \Magento\SalesRule\Model\Utility
     */
    protected $validatorUtility;

    /**
     * @var \Magento\SalesRule\Model\RulesApplier
     */
    protected $rulesApplier;
    /**
     * @var CartFixedDiscount
     */
    private $cartFixedDiscountHelper;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping
     */
    protected $resourceQuoteShipping;

    /**
     * @var \Vnecoms\VendorsShipping\Helper\Data
     */
    protected $helper;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Validator constructor.
     * @param CollectionFactory $collectionFactory
     * @param \Magento\SalesRule\Model\Utility $utility
     * @param \Magento\SalesRule\Model\RulesApplier $rulesApplier
     * @param PriceCurrencyInterface $priceCurrency
     * @param CartFixedDiscount $cartFixedDiscount
     * @param \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping $resourceQuoteShipping
     * @param \Vnecoms\VendorsShipping\Helper\Data $helper
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\SalesRule\Model\Utility $utility,
        \Magento\SalesRule\Model\RulesApplier $rulesApplier,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        CartFixedDiscount $cartFixedDiscount,
        \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping $resourceQuoteShipping,
        \Vnecoms\VendorsShipping\Helper\Data $helper,
        \Vnecoms\Vendors\Helper\Data $vendorHelper
    ) {
        $this->_vendorHelper = $vendorHelper;
        $this->helper = $helper;
        $this->validatorUtility = $utility;
        $this->rulesApplier = $rulesApplier;
        $this->cartFixedDiscountHelper = $cartFixedDiscount;
        $this->_collectionFactory = $collectionFactory;
        $this->resourceQuoteShipping = $resourceQuoteShipping;
        $this->priceCurrency   = $priceCurrency;
    }

    /**
     * @param \Magento\SalesRule\Model\Validator $subject
     * @param callable $proceed
     * @param Address $address
     * @return $this
     */
    public function aroundProcessShippingAmount(
        \Magento\SalesRule\Model\Validator $subject,
        callable $proceed,
        Address $address
    )
    {
        if (!$this->_vendorHelper->moduleEnabled() || !$this->helper->isEnabled()) {
            return $proceed($address);
        }
        $quote = $address->getQuote();
        $this->_updateVendorsShippingQuote($quote);

        $mainShippingAmount = $address->getShippingAmountForDiscount();
        if ($mainShippingAmount !== null) {
            $mainBaseShippingAmount = $address->getBaseShippingAmountForDiscount();
        } else {
            $mainShippingAmount = $address->getShippingAmount();
            $mainBaseShippingAmount = $address->getBaseShippingAmount();
        }

        $appliedRuleIds = [];

        $groupRulesByVendors = $this->_getRules($subject, $address);
        $groupRulesByVendors = $this->_getAvailableRulesByVendor($groupRulesByVendors);

        foreach ($groupRulesByVendors as $vendorId => $rules) {
            if ($vendorId == "no-vendor") {
                $vendorShippingDatas =  $this->resourceQuoteShipping->getAllShippingVendorByQuote($quote->getId());
                if ($vendorShippingDatas) {
                    foreach ($vendorShippingDatas as $vendorShippingData) {
                        $appliedRuleIds = $this->_calRuleProcess(
                            $subject,
                            $mainShippingAmount,
                            $mainBaseShippingAmount,
                            $vendorShippingData,
                            $rules,
                            $address,
                            $quote,
                            $appliedRuleIds
                        );
                    }
                }
            } else {
                $vendorShippingData =  $this->resourceQuoteShipping->getShippingQuoteByVendor($vendorId, $quote->getId());
                if ($vendorShippingData) {
                    $appliedRuleIds = $this->_calRuleProcess(
                        $subject,
                        $mainShippingAmount,
                        $mainBaseShippingAmount,
                        $vendorShippingData,
                        $rules,
                        $address,
                        $quote,
                        $appliedRuleIds
                    );
                }
            }
        }

        $address->setAppliedRuleIds($this->validatorUtility->mergeIds($address->getAppliedRuleIds(), $appliedRuleIds));
        $quote->setAppliedRuleIds($this->validatorUtility->mergeIds($quote->getAppliedRuleIds(), $appliedRuleIds));

        return $this;
    }

    /**
     * @param $subject
     * @param $mainShippingAmount
     * @param $mainBaseShippingAmount
     * @param $vendorShippingData
     * @param $rules
     * @param $address
     * @param $quote
     * @param $appliedRuleIds
     * @return mixed
     */
    private function _calRuleProcess(
        $subject,
        $mainShippingAmount,
        $mainBaseShippingAmount,
        $vendorShippingData,
        $rules,
        $address,
        $quote,
        $appliedRuleIds
    ) {

        $coreBaseShippingAmount = $vendorShippingData['shipping_amount'];
        $coreBaseDiscountShippingAmount = $vendorShippingData['discount_shipping_amount'];
        if (!$coreBaseShippingAmount) $appliedRuleIds;
        $coreShippingAmount = $this->priceCurrency->convert($vendorShippingData['shipping_amount'], null, $quote->getData('quote_currency_code'));
        $coreDiscountShippingAmount = $this->priceCurrency->convert($vendorShippingData['discount_shipping_amount'], null, $quote->getData('quote_currency_code'));

        $shippingAmount = $coreShippingAmount;
        $baseShippingAmount = $coreBaseShippingAmount;
        $totalBaseDiscount = $coreBaseDiscountShippingAmount;
        $totalDiscount = $coreDiscountShippingAmount;

        foreach ($rules as $rule) {
            /* @var Rule $rule */
            if (!$rule->getApplyToShipping() || !$this->validatorUtility->canProcessRule($rule, $address)) {
                continue;
            }
            $discountAmount = 0;
            $baseDiscountAmount = 0;
            $rulePercent = min(100, $rule->getDiscountAmount());
            switch ($rule->getSimpleAction()) {
                case Rule::TO_PERCENT_ACTION:
                    $rulePercent = max(0, 100 - $rule->getDiscountAmount());
                // break is intentionally omitted
                // no break
                case Rule::BY_PERCENT_ACTION:
                    $discountAmount = ($shippingAmount - $totalDiscount) * $rulePercent / 100;
                    $baseDiscountAmount = ($baseShippingAmount -
                            $totalBaseDiscount) * $rulePercent / 100;
                    $discountPercent = min(100, $address->getShippingDiscountPercent() + $rulePercent);
                    $address->setShippingDiscountPercent($discountPercent);

                    break;
                case Rule::TO_FIXED_ACTION:
                    $quoteAmount = $this->priceCurrency->convert($rule->getDiscountAmount(), $quote->getStore());
                    $discountAmount = $shippingAmount - $quoteAmount;
                    $baseDiscountAmount = $baseShippingAmount - $rule->getDiscountAmount();
                    break;
                case Rule::BY_FIXED_ACTION:
                    $quoteAmount = $this->priceCurrency->convert($rule->getDiscountAmount(), $quote->getStore());
                    $discountAmount = $quoteAmount;
                    $baseDiscountAmount = $rule->getDiscountAmount();
                    break;
                case Rule::CART_FIXED_ACTION:
                    $cartRules = $address->getCartFixedRules();
                    $quoteAmount = $this->priceCurrency->convert($rule->getDiscountAmount(), $quote->getStore());
                    $isAppliedToShipping = (int) $rule->getApplyToShipping();
                    if (!isset($cartRules[$rule->getId()])) {
                        $cartRules[$rule->getId()] = $rule->getDiscountAmount();
                    }
                    if ($cartRules[$rule->getId()] > 0) {
                        $shippingAmount = $coreBaseShippingAmount - $coreBaseDiscountShippingAmount;
                        $quoteBaseSubtotal = (float) $quote->getBaseSubtotal();
                        $isMultiShipping = $this->cartFixedDiscountHelper->checkMultiShippingQuote($quote);
                        if ($isAppliedToShipping) {
                            $quoteBaseSubtotal = ($quote->getIsMultiShipping() && $isMultiShipping) ?
                                $this->cartFixedDiscountHelper->getQuoteTotalsForMultiShipping($quote) :
                                $this->cartFixedDiscountHelper->getQuoteTotalsForRegularShipping(
                                    $address,
                                    $quoteBaseSubtotal
                                );
                            $discountAmount = $this->cartFixedDiscountHelper->
                            getShippingDiscountAmount(
                                $rule,
                                $shippingAmount,
                                $quoteBaseSubtotal
                            );
                            $baseDiscountAmount = $discountAmount;
                        } else {
                            $discountAmount = min($shippingAmount, $quoteAmount);
                            $baseDiscountAmount = min(
                                $baseShippingAmount - $coreBaseDiscountShippingAmount,
                                $cartRules[$rule->getId()]
                            );
                        }
                        $cartRules[$rule->getId()] -= $baseDiscountAmount;
                    }
                    $address->setCartFixedRules($cartRules);
                    break;
                case Rule::BUY_X_GET_Y_ACTION:
                    $allQtyDiscount = $this->getDiscountQtyAllItemsBuyXGetYAction($quote, $rule);
                    $quoteAmount = $coreBaseShippingAmount / $quote->getItemsQty() * $allQtyDiscount;
                    $discountAmount = $this->priceCurrency->convert($quoteAmount, $quote->getStore());
                    $baseDiscountAmount = $quoteAmount;
                    break;
            }

            $totalBaseDiscount += $this->priceCurrency->roundPrice($baseDiscountAmount);
            $totalDiscount += $this->priceCurrency->roundPrice($discountAmount);
            $totalBaseDiscount = min($totalBaseDiscount, $coreBaseShippingAmount);
            $totalDiscount = min($totalDiscount, $coreShippingAmount);

            $this->resourceQuoteShipping->updateDiscountVendorShippingQuoteById($vendorShippingData['entity_id'], $totalBaseDiscount);

            $discountAmount = min($address->getShippingDiscountAmount() + $discountAmount, $mainShippingAmount);
            $baseDiscountAmount = min(
                $address->getBaseShippingDiscountAmount() + $baseDiscountAmount,
                $mainBaseShippingAmount
            );
            $address->setShippingDiscountAmount($this->priceCurrency->roundPrice($discountAmount));
            $address->setBaseShippingDiscountAmount($this->priceCurrency->roundPrice($baseDiscountAmount));

            $appliedRuleIds[$rule->getRuleId()] = $rule->getRuleId();

            $this->rulesApplier->maintainAddressCouponCode($address, $rule, $subject->getCouponCode());
            $this->rulesApplier->addDiscountDescription($address, $rule);


            if ($rule->getStopRulesProcessingVendor()) {
                break;
            }
        }
        return $appliedRuleIds;
    }

    /**
     * @param $rules
     * @return array
     */
    private function _getAvailableRulesByVendor($rules)
    {
        $vendorRules = [];
        foreach ($rules as $rule) {
            if($rule->getVendorId()) {
                /*Get item by vendor id*/
                if (!isset($vendorRules[$rule->getVendorId()])) $vendorRules[$rule->getVendorId()] = [];
                $vendorRules[$rule->getVendorId()][] = $rule;
            } else {
                $vendorRules['no-vendor'][] = $rule;
            }
            if ($rule->getStopRulesProcessing()) {
                break;
            }
        }
        return $vendorRules;
    }

    /**
     * Return discount Qty for all items at Buy_X_Get_Y_Action
     *
     * @param Quote $quote
     * @param Rule $rule
     * @return float
     */
    private function getDiscountQtyAllItemsBuyXGetYAction(Quote $quote, Rule $rule): float
    {
        $discountAllQty = 0;
        foreach ($quote->getItems() as $item) {
            $qty = $item->getQty();

            $discountStep = $rule->getDiscountStep();
            $discountAmount = $rule->getDiscountAmount();
            if (!$discountStep || $discountAmount > $discountStep) {
                continue;
            }
            $buyAndDiscountQty = $discountStep + $discountAmount;

            $fullRuleQtyPeriod = floor($qty / $buyAndDiscountQty);
            $freeQty = $qty - $fullRuleQtyPeriod * $buyAndDiscountQty;

            $discountQty = $fullRuleQtyPeriod * $discountAmount;
            if ($freeQty > $discountStep) {
                $discountQty += $freeQty - $discountStep;
            }

            $discountAllQty += $discountQty;
        }

        return $discountAllQty;
    }

    /**
     * Get rules collection for current object state
     *
     * @param Address|null $address
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     * @throws \Zend_Db_Select_Exception
     */
    protected function _getRules($subject, Address $address = null)
    {
        $addressId = $this->getAddressId($address);
        $key = $subject->getWebsiteId() . '_'
            . $subject->getCustomerGroupId() . '_'
            . $subject->getCouponCode() . '_'
            . $addressId;
        if (!isset($this->_rules[$key])) {
            $this->_rules[$key] = $this->_collectionFactory->create()
                ->setValidationFilter(
                    $subject->getWebsiteId(),
                    $subject->getCustomerGroupId(),
                    $subject->getCouponCode(),
                    null,
                    $address
                )
                ->addFieldToFilter('is_active', 1)
                ->load();
        }
        return $this->_rules[$key];
    }

    /**
     * @param Address $address
     * @return string
     */
    protected function getAddressId(Address $address)
    {
        if ($address == null) {
            return '';
        }
        if (!$address->hasData('address_sales_rule_id')) {
            if ($address->hasData('address_id')) {
                $address->setData('address_sales_rule_id', $address->getData('address_id'));
            } else {
                $type = $address->getAddressType();
                $tempId = $type . $this->counter++;
                $address->setData('address_sales_rule_id', $tempId);
            }
        }
        return $address->getData('address_sales_rule_id');
    }

    /**
     * @param $quote
     */
    private function _updateVendorsShippingQuote($quote)
    {
        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        $shippingMethod = str_replace('vendor_multirate_', '', $shippingMethod);
        $shippingMethods = explode(
            \Vnecoms\VendorsShipping\Plugin\Shipping::METHOD_SEPARATOR,
            $shippingMethod
        );

        $vendorShippingMethod = [];
        foreach ($shippingMethods as $method) {
            $tmpMethods = explode(
                \Vnecoms\VendorsShipping\Plugin\Shipping::SEPARATOR,
                $method
            );

            if (sizeof($tmpMethods) != 2) {
                continue;
            }
            $vendorShippingMethod[$tmpMethods[1]] = $method;
            $shippingRate = $quote->getShippingAddress()->getShippingRateByCode($method);

            if (!$shippingRate || !$shippingRate->getId()) {
                continue;
            }

            $des =  $shippingRate->getCarrierTitle().' - '.$shippingRate->getMethodTitle();
            $amount = $shippingRate->getPrice();
            $method = $shippingRate->getCode();
            $this->resourceQuoteShipping->saveVendorShippingQuote(
                $tmpMethods[1],
                $quote->getId(),
                $amount,
                $method,
                $des
            );
        }
    }
}
