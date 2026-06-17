<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShipping\Model\SalesRule;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Quote\ChildrenValidationLocator;
use Magento\Framework\App\ObjectManager;

/**
 * Class RulesApplier
 * @package Magento\SalesRule\Model\Validator
 */
class RulesApplier extends \Magento\SalesRule\Model\RulesApplier
{
    /**
     * @var ChildrenValidationLocator
     */
    private $childrenValidationLocator;

    /**
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $calculatorFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\SalesRule\Model\Utility $utility
     * @param ChildrenValidationLocator $childrenValidationLocator
     */
    public function __construct(
        \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $calculatorFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\SalesRule\Model\Utility $utility,
        ChildrenValidationLocator $childrenValidationLocator = null
    ) {
        parent::__construct($calculatorFactory, $eventManager, $utility, $childrenValidationLocator);
        $this->childrenValidationLocator = $childrenValidationLocator
            ?: ObjectManager::getInstance()->get(ChildrenValidationLocator::class);
    }

    /**
     * Apply rules to current order item
     *
     * @param AbstractItem $item
     * @param array $rules
     * @param bool $skipValidation
     * @param string $couponCodes
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function applyRules($item, $rules, $skipValidation, array $couponCodes = [])
    {
        $address = $item->getAddress();
        $appliedRuleIds = [];
        $vendorId = $item->getProduct()->getVendorId();

        if (!$vendorId) {
            return parent::applyRules($item, $rules, $skipValidation, $couponCodes);
        }
        $rules = $this->_getAvailableRulesByVendor($vendorId, $rules);


        /* @var $rule \Magento\SalesRule\Model\Rule */
        foreach ($rules as $rule) {
            if (!$this->validatorUtility->canProcessRule($rule, $address)) {
                continue;
            }

            if (!$skipValidation && !$rule->getActions()->validate($item)) {
                if (!$this->childrenValidationLocator->isChildrenValidationRequired($item)) {
                    continue;
                }
                $childItems = $item->getChildren();
                $isContinue = true;
                if (!empty($childItems)) {
                    foreach ($childItems as $childItem) {
                        if ($rule->getActions()->validate($childItem)) {
                            $isContinue = false;
                        }
                    }
                }
                if ($isContinue) {
                    continue;
                }
            }

            $this->applyRule($item, $rule, $address, $couponCodes);
            $appliedRuleIds[$rule->getRuleId()] = $rule->getRuleId();

            if ($rule->getStopRulesProcessing() || $rule->getStopRulesProcessingVendor()) {
                break;
            }
        }

        return $appliedRuleIds;
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
