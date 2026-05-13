<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Shopping Cart Rule data model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\VendorsShipping\Model\OfflineShipping\SalesRule;

use  Magento\OfflineShipping\Model\SalesRule\Rule;

/**
 * @api
 * @since 100.0.2
 */
class Calculator extends \Magento\OfflineShipping\Model\SalesRule\Calculator
{
    /**
     * Quote item free shipping ability check
     * This process not affect information about applied rules, coupon code etc.
     * This information will be added during discount amounts processing
     *
     * @param   \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return  \Magento\OfflineShipping\Model\SalesRule\Calculator
     */
    public function processFreeShipping(\Magento\Quote\Model\Quote\Item\AbstractItem $item)
    {
        $vendorId = $item->getProduct()->getVendorId();
        if (!$vendorId) return parent::processFreeShipping($item);

        $address = $item->getAddress();
        $item->setFreeShipping(false);
        $mainRules = $this->_getRules($address);
        $rules = $this->_getAvailableRulesByVendor($vendorId, $mainRules);

        foreach ($rules as $rule) {
            /* @var $rule \Magento\SalesRule\Model\Rule */
            if (!$this->validatorUtility->canProcessRule($rule, $address)) {
                continue;
            }

            if (!$rule->getActions()->validate($item)) {
                continue;
            }

            switch ($rule->getSimpleFreeShipping()) {
                case Rule::FREE_SHIPPING_ITEM:
                    $item->setFreeShipping($rule->getDiscountQty() ? $rule->getDiscountQty() : true);
                    break;

                case Rule::FREE_SHIPPING_ADDRESS:
                    $address->setFreeShipping(true);
                    break;
            }
            if ($rule->getStopRulesProcessing() || $rule->getStopRulesProcessingVendor()) {
                break;
            }
        }
        return $this;
    }

    /**
     * @param $vendorId
     * @param $rules
     * @return array
     */
    private function _getAvailableRulesByVendor($vendorId, $rules)
    {
        $vendorRules = [];
        foreach ($rules as $rule) {
            if ($rule->getVendorId()) {
                $vendorsId = explode(",", $rule->getVendorId());
                if ($rule->getVendorId() && in_array($vendorId, $vendorsId)) {
                    $vendorRules[$rule->getId()] = $rule;
                }
            }
             else {
                $vendorRules[$rule->getId()] = $rule;
            }
        }
        return $vendorRules;
    }
}
