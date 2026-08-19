<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * "You qualify for free shipping" / "Spend X more" for the cart summary.
 *
 * THE THRESHOLD IS READ FROM THE RULE, NEVER HARD-CODED. This install grants
 * free shipping through a cart price rule ("Spend $50 or more - shipping is
 * free!", base_subtotal >= 50, no end date), not through the freeshipping
 * carrier, which is switched off. A literal 50 in a template would be right
 * until someone edits the rule and then quietly wrong forever, the same trap as
 * putting a seller count in a CMS block.
 *
 * Only rules that could actually apply are considered: active, in date, for this
 * website, for the customer's group, and WITHOUT a coupon requirement — a rule
 * you must type a code to get is not a promise the cart can make unprompted.
 *
 * Everything is wrapped: a broken or unusual rule condition tree must not take
 * the cart page down, and the notice is decoration. On any failure it renders
 * nothing.
 */
class FreeShipping implements ArgumentInterface
{
    private ?array $state = null;

    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly RuleCollectionFactory $ruleCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /** Has the quote already earned free shipping? */
    public function isQualified(): bool
    {
        return (bool) ($this->resolve()['qualified'] ?? false);
    }

    /** Amount still to spend, in base currency, or null when not applicable. */
    public function getRemaining(): ?float
    {
        $r = $this->resolve()['remaining'] ?? null;
        return $r !== null && $r > 0 ? (float) $r : null;
    }

    /** True when there is anything at all to say. */
    public function hasNotice(): bool
    {
        return $this->isQualified() || $this->getRemaining() !== null;
    }

    private function resolve(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }
        $this->state = ['qualified' => false, 'remaining' => null];

        try {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote || !$quote->getId() || !$quote->getItemsCount()) {
                return $this->state;
            }

            /*
             * Already free? Ask the quote, not the rule. The shipping address
             * carries the flag once any rule, coupon or carrier has granted it,
             * so this stays true for reasons this class does not need to know.
             */
            $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
            if ($address && $address->getFreeShipping()) {
                $this->state['qualified'] = true;
                return $this->state;
            }

            $threshold = $this->lowestThreshold((int) $quote->getCustomerGroupId());
            if ($threshold === null) {
                return $this->state;
            }

            $subtotal = (float) $quote->getBaseSubtotal();
            if ($subtotal >= $threshold) {
                //  Over the line but the flag is not set yet — totals may not
                //  have been collected. Treat as qualified rather than telling
                //  the shopper to spend a negative amount.
                $this->state['qualified'] = true;
                return $this->state;
            }
            $this->state['remaining'] = $threshold - $subtotal;
        } catch (\Throwable $e) {
            $this->logger->warning('hm free-shipping notice skipped: ' . $e->getMessage());
        }

        return $this->state;
    }

    /**
     * Lowest base_subtotal threshold among rules that could apply without a
     * coupon. Returns null when no such rule exists — which is the correct
     * answer for a store that does not offer free shipping.
     */
    private function lowestThreshold(int $customerGroupId): ?float
    {
        $websiteId = (int) $this->storeManager->getStore()->getWebsiteId();

        $rules = $this->ruleCollectionFactory->create()
            ->setValidationFilter($websiteId, $customerGroupId)
            ->addFieldToFilter('simple_free_shipping', ['gt' => 0]);

        $lowest = null;
        foreach ($rules as $rule) {
            //  NO_COUPON only. A rule needing a code is not an unprompted promise.
            if ((int) $rule->getCouponType() !== \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON) {
                continue;
            }
            foreach ($this->subtotalConditions($rule) as $value) {
                $lowest = $lowest === null ? $value : min($lowest, $value);
            }
        }
        return $lowest;
    }

    /** Every `base_subtotal >=` value in a rule's condition tree. */
    private function subtotalConditions($rule): array
    {
        $out = [];
        try {
            $conditions = $rule->getConditions();
            foreach ((array) $conditions->getConditions() as $condition) {
                if ($condition->getAttribute() !== 'base_subtotal') {
                    continue;
                }
                if (!in_array($condition->getOperator(), ['>=', '>'], true)) {
                    continue;
                }
                $out[] = (float) $condition->getValue();
            }
        } catch (\Throwable $e) {
            $this->logger->warning('hm free-shipping rule unreadable: ' . $e->getMessage());
        }
        return $out;
    }
}
