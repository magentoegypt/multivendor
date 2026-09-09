<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\FormatInterface;
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

    private ?float $thresholdCache = null;

    private bool $thresholdResolved = false;

    private HttpContext $httpContext;

    private FormatInterface $localeFormat;

    /*
     * THE LAST TWO ARGUMENTS ARE OPTIONAL ON PURPOSE, and this is not laziness.
     *
     * This install runs production mode with compiled DI. Magento's compiled
     * object factory reads each class's constructor arguments from
     * generated/metadata, and this class is IN that map with four arguments —
     * from the last compile. Adding a fifth REQUIRED argument would make the
     * factory call the constructor with the four it knows about and fail with
     * an ArgumentCountError, and `setup:di:compile` on this two-core box means
     * taking the storefront down while it runs.
     *
     * Optional-with-ObjectManager-fallback is Magento's own idiom for exactly
     * this — core uses it throughout to add constructor dependencies without
     * breaking installs that have not recompiled. Once a compile does happen
     * these resolve through DI normally and the fallback never runs.
     */
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly RuleCollectionFactory $ruleCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        ?HttpContext $httpContext = null,
        ?FormatInterface $localeFormat = null
    ) {
        $this->httpContext  = $httpContext ?: ObjectManager::getInstance()->get(HttpContext::class);
        $this->localeFormat = $localeFormat ?: ObjectManager::getInstance()->get(FormatInterface::class);
    }

    /**
     * The free-shipping threshold on its own, with NO reference to the quote.
     *
     * The mini-cart needs this and only this from the server. Everything else —
     * how much the shopper has in the basket, how much is left to go — is read
     * client-side from the `cart` customer-data section, because the mini-cart
     * sits in the header of a full-page-cached page and per-visitor figures
     * baked into it would be one shopper's numbers shown to the next.
     *
     * Safe to render into a cached page: the only thing it varies on is the
     * customer group, which the full-page cache already varies its entries by
     * (Magento\Customer\Model\Context::CONTEXT_GROUP), and it is read here
     * from that same cache context rather than from the session — asking the
     * session would start one, and starting a session on a cacheable page
     * creates a quote for every crawler that touches the site.
     *
     * @return float|null null when the store offers no unprompted free shipping
     */
    public function getThreshold(): ?float
    {
        if ($this->thresholdResolved) {
            return $this->thresholdCache;
        }
        $this->thresholdResolved = true;

        try {
            $group = (int) $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP);
            $this->thresholdCache = $this->lowestThreshold($group);
        } catch (\Throwable $e) {
            $this->logger->warning('hm free-shipping threshold unreadable: ' . $e->getMessage());
            $this->thresholdCache = null;
        }

        return $this->thresholdCache;
    }

    /**
     * The store's price format, in the shape Magento_Catalog/js/price-utils
     * expects. The mini-cart bar has to render "Add EGP 42 more" from a number
     * it computes in the browser, so it needs the same formatting rules the
     * rest of the storefront uses rather than a hand-rolled currency string.
     */
    public function getPriceFormat(): array
    {
        try {
            return $this->localeFormat->getPriceFormat();
        } catch (\Throwable $e) {
            $this->logger->warning('hm price format unreadable: ' . $e->getMessage());
            return [];
        }
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
