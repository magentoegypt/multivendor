<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Config;

use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * hmAppConfig.shipping.free_over: the cart subtotal that earns free shipping,
 * the figure the storefront's mini-cart counts down to ("Add AED 7 more for
 * free shipping").
 *
 * The website's own rule, MagentoEgypt\CheckoutExtend\ViewModel\FreeShipping
 * (getThreshold / lowestThreshold), read the same way: this store grants free
 * shipping through a cart price rule ("base_subtotal >= 50"), not through the
 * freeshipping carrier, which is switched off in both store views. So the
 * threshold is the lowest `base_subtotal >=` (or `>`) value among the rules
 * that could apply without being asked for: active and in date for the store's
 * website and the customer group (SalesRule setValidationFilter), granting
 * free shipping (simple_free_shipping), and needing NO coupon; a rule you must
 * type a code for is not a promise the cart can make. Null when there is no
 * such rule, which is the right answer for a store without free shipping.
 *
 * Like the view model, the value is the rule's number, in the base currency,
 * and a broken rule is skipped rather than failing hmAppConfig.
 */
class FreeShippingThreshold
{
    /** Comparisons that promise free shipping from a subtotal upwards. */
    private const OPERATORS = ['>=', '>'];

    private const ATTRIBUTE = 'base_subtotal';

    public function __construct(
        private readonly RuleCollectionFactory $ruleCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The threshold for $customerGroupId on $storeId's website, base currency; null when none.
     */
    public function forStore(int $storeId, int $customerGroupId): ?float
    {
        try {
            $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
            $rules = $this->ruleCollectionFactory->create()
                ->setValidationFilter($websiteId, $customerGroupId)
                ->addFieldToFilter('simple_free_shipping', ['gt' => 0]);

            $candidates = [];
            foreach ($rules as $rule) {
                $candidates[] = [
                    'coupon_type' => (int) $rule->getCouponType(),
                    'conditions' => $this->topConditions($rule),
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: free-shipping threshold unreadable: ' . $e->getMessage());

            return null;
        }

        return self::lowest($candidates);
    }

    /**
     * Lowest subtotal promise of coupon-free rules. Pure: unit-tested.
     *
     * @param array<int, array{coupon_type: int, conditions: array<int, array{attribute: string, operator: string, value: mixed}>}> $rules
     */
    public static function lowest(array $rules): ?float
    {
        $lowest = null;
        foreach ($rules as $rule) {
            if ((int) $rule['coupon_type'] !== Rule::COUPON_TYPE_NO_COUPON) {
                continue;
            }
            foreach ($rule['conditions'] as $condition) {
                if ($condition['attribute'] !== self::ATTRIBUTE
                    || !in_array($condition['operator'], self::OPERATORS, true)
                    || !is_numeric($condition['value'])
                ) {
                    continue;
                }
                $value = (float) $condition['value'];
                if ($value < 0) {
                    continue;
                }
                $lowest = $lowest === null ? $value : min($lowest, $value);
            }
        }

        return $lowest;
    }

    /**
     * The rule's first-level conditions, as the website's view model reads them.
     *
     * @return array<int, array{attribute: string, operator: string, value: mixed}>
     */
    private function topConditions(Rule $rule): array
    {
        $out = [];
        try {
            foreach ((array) $rule->getConditions()->getConditions() as $condition) {
                $out[] = [
                    'attribute' => (string) $condition->getAttribute(),
                    'operator' => (string) $condition->getOperator(),
                    'value' => $condition->getValue(),
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: free-shipping rule ' . (int) $rule->getId() . ' unreadable: ' . $e->getMessage());
        }

        return $out;
    }
}
