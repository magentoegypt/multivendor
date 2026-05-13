<?php

namespace Vnecoms\VendorsShipping\Plugin\SalesRule\Model;

/**
 * @inheritdoc
 */
class RulesApplier
{
    /**
     * @param \Magento\SalesRule\Model\RulesApplier $subject
     * @param $item
     * @param $rules
     * @param $skipValidation
     * @param $couponCode
     * @return array
     */
    public function beforeApplyRules(
        \Magento\SalesRule\Model\RulesApplier $subject,
        $item,
        $rules,
        $skipValidation,
        $couponCode
    )
    {
        $vendorId = $item->getProduct()->getVendorId();
        if ($vendorId) {
            $rules = $this->_getAvailableRulesByVendor($vendorId, $rules);
        }
        return [$item, $rules, $skipValidation, $couponCode];
    }

    /**
     * @param $vendorId
     * @param $rules
     * @param $oldRules
     * @return array
     */
    private function _getAvailableRulesByVendor($vendorId, $rules)
    {
        $vendorRules = [];
        foreach ($rules as $rule) {
            $vendorsId = explode(",", $rule->getVendorId());
            if ($rule->getVendorId() && in_array($vendorId, $vendorsId)) {
                $vendorRules[$rule->getId()] = $rule;
                if ($rule->getStopRulesProcessingVendor()) {
                    break;
                }
            } else {
                $vendorRules[$rule->getId()] = $rule;
            }
        }
        return $vendorRules;
    }
}
