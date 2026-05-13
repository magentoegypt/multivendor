<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 20/12/2016
 * Time: 17:57.
 */

namespace Vnecoms\VendorsCommission\Helper;

use Vnecoms\VendorsCommission\Model\Rule as CommissionRule;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Vnecoms\VendorsCommission\Model\Rule
     */
    protected $_ruleFactory;

    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    protected $_catalogRuleFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Vnecoms\VendorsCommission\Model\RuleFactory $ruleFactory
     * @param \Vnecoms\VendorsCommission\Model\TmpRuleFactory $catalogRuleFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Vnecoms\VendorsCommission\Model\RuleFactory $ruleFactory,
        \Vnecoms\VendorsCommission\Model\TmpRuleFactory $catalogRuleFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_ruleFactory = $ruleFactory;
        $this->_catalogRuleFactory = $catalogRuleFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param $vendorGroupId
     * @param $websiteId
     * @param $product
     * @param $item
     * @param $order
     * @param $fee
     * @return array
     * @throws \Exception
     */
    public function getFeeCommission(
        $vendorGroupId,
        $websiteId,
        $product,
        $item,
        $order,
        $fee = 0
    ) {
        $ruleCollection = $this->_ruleFactory->create()->getCollection()
            ->addFieldToFilter('vendor_group_ids', ['finset'=>$vendorGroupId])
            ->addFieldToFilter('website_ids', ['finset'=>$websiteId])
            ->addFieldToFilter('is_active', CommissionRule::STATUS_ENABLED);

        $today = (new \DateTime())->format('Y-m-d');
        $ruleCollection->getSelect()
            ->where(
                '(from_date IS NULL OR from_date<=?) AND (to_date IS NULL OR to_date>=?)',
                $today,
                $today
            )->order('priority ASC');
        $ruleDescriptionArr = [];

        if ($ruleCollection->count()) {
            foreach ($ruleCollection as $rule) {
                $tmpRule = $this->_catalogRuleFactory->create();
                /*If the product is not match with the conditions just continue*/
                $tmpRule->setConditionsSerialized($rule->getConditionSerialized());
                if (!$tmpRule->getConditions()->validate($product)) {
                    continue;
                }
                $tmpFee = 0;
                switch ($rule->getData('commission_by')) {
                    case CommissionRule::COMMISSION_BY_FIXED_AMOUNT:
                        $tmpFee = $rule->getData('commission_amount') * $item->getData('qty');
                        break;
                    case CommissionRule::COMMISSION_BY_PERCENT_PRODUCT_PRICE:
                        if (!$item->getData('base_row_total')) {
                            $baseRowTotal = ($item->getData('price_incl_tax') * $item->getData('qty')) - $item->getData('base_tax_amount');
                            $item->setData('base_row_total', $baseRowTotal);
                        }
                        switch ($rule->getData('commission_action')) {
                            case CommissionRule::COMMISSION_BASED_PRICE_INCL_TAX:
                                $amount = $item->getData('base_row_total') + $item->getData('base_tax_amount');
                                break;
                            case CommissionRule::COMMISSION_BASED_PRICE_EXCL_TAX:
                                $amount = $item->getData('base_row_total');
                                break;
                            case CommissionRule::COMMISSION_BASED_PRICE_AFTER_DISCOUNT_INCL_TAX:
                                $amount = $item->getData('base_row_total') - $item->getData('base_discount_amount') + $item->getData('base_tax_amount');
                                break;
                            case CommissionRule::COMMISSION_BASED_PRICE_AFTER_DISCOUNT_EXCL_TAX:
                                $amount = $item->getData('base_row_total')  - $item->getData('base_discount_amount');
                                break;
                            default:
                                $amount = $item->getData('base_row_total')  - $item->getData('base_discount_amount');
                        }
                        $tmpFee = ($rule->getData('commission_amount') * $amount)/100;
                        break;
                }
                $tmpFeeWithCurrency = $order->formatBasePrice($tmpFee);

                $title = $rule->getDescription() ? $rule->getDescription() : $rule->getName();
                $ruleDescriptionArr[] = $title.": ".$tmpFeeWithCurrency;

                $fee +=  $tmpFee;

                /*Break if the flag stop rules processing is set to 1*/
                if ($rule->getData('stop_rules_processing')) {
                    break;
                }
            }
            return [
                'fee' => $fee,
                'description' => $ruleDescriptionArr
            ];
        }
    }
}
