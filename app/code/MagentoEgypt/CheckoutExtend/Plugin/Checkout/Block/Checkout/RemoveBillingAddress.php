<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

/**
 * Takes the billing address form out of the payment step — EXCEPT on a virtual cart.
 *
 * WHY A PLUGIN AND NOT LAYOUT XML
 * -------------------------------
 * The form is not declared anywhere in checkout_index_index.xml. LayoutProcessor
 * builds it in PHP and merges it into the payment component at runtime — either
 * one shared form under `afterMethods`, or one per method under `payments-list`,
 * depending on checkout/options/display_billing_address_on. Nothing in jsLayout
 * can disable a node that jsLayout never declared, so it is unset after the fact.
 * Both shapes are handled, so flipping that setting cannot bring it back.
 *
 * WHY THIS DOES NOT LOSE THE BILLING ADDRESS — ON A PHYSICAL CART
 * --------------------------------------------------------------
 * Only the UI goes. The address is still set, by the path Magento already uses
 * when a shopper leaves "My billing and shipping address are the same" ticked:
 * shipping.js's setShippingInformation() clears quote.billingAddress() and calls
 * checkoutDataResolver.resolveBillingAddress(), whose applyBillingAddress() falls
 * through to selectBillingAddress(quote.shippingAddress()). The order therefore
 * still carries a billing address, and it is the shipping one.
 *
 * This is a single-shipping-address storefront with no separate billing flow, so
 * the form only ever offered the shopper the chance to disagree with themselves.
 *
 * AND WHY A VIRTUAL CART HAD TO BE CARVED OUT (2026-09-22)
 * -------------------------------------------------------
 * Every word above depends on there BEING a shipping address to fall back to.
 * A cart of only virtual or downloadable items has no shipping step at all, so
 * setShippingInformation() never runs, resolveBillingAddress() is never reached,
 * and selectBillingAddress() never fires. Removing the form there does not move
 * the address collection elsewhere — it removes the only place the shopper could
 * ever have entered one.
 *
 * Reproduced as a guest with a virtual product: the payment step rendered an
 * e-mail field, the security notice and a disabled Review Order button. No
 * address inputs in the DOM at all (measured: 0), and no way to proceed. A
 * virtual order still legitimately needs a billing address — for tax, for the
 * invoice, and because Magento requires one to place the order.
 *
 * So: on a virtual quote, leave Magento's own layout alone. It already renders
 * the billing form in the payment step precisely because there is no other
 * address on the order.
 */
class RemoveBillingAddress
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Both arguments are optional ON PURPOSE.
     *
     * This class was compiled into production DI with NO constructor at all, and
     * a compiled factory calls the constructor with the argument list it was
     * compiled against. A required parameter would therefore be an
     * ArgumentCountError on the first checkout render, before anyone ran
     * di:compile. Optional + an ObjectManager fallback is the safe way to take a
     * dependency on an already-compiled class.
     *
     * @param CheckoutSession|null $checkoutSession
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ?CheckoutSession $checkoutSession = null,
        ?LoggerInterface $logger = null
    ) {
        $this->checkoutSession = $checkoutSession ?: ObjectManager::getInstance()->get(CheckoutSession::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * @param  LayoutProcessor $subject
     * @param  array           $jsLayout
     * @return array
     */
    public function afterProcess(LayoutProcessor $subject, $jsLayout)
    {
        if ($this->isVirtualQuote()) {
            return $jsLayout;
        }

        if (!isset(
            $jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
        )) {
            return $jsLayout;
        }

        $payment = &$jsLayout['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children'];

        // Shared form: display_billing_address_on = payment page.
        unset($payment['afterMethods']['children']['billing-address-form']);

        // Per-method forms: display_billing_address_on = payment method.
        if (isset($payment['payments-list']['children'])) {
            foreach (array_keys($payment['payments-list']['children']) as $child) {
                if (substr((string) $child, -5) === '-form') {
                    unset($payment['payments-list']['children'][$child]);
                }
            }
        }

        return $jsLayout;
    }

    /**
     * Is the current quote entirely virtual or downloadable?
     *
     * Failure is deliberately treated as "not virtual", i.e. the form is removed
     * as it always was. This runs while the checkout page is being rendered, and
     * a session or quote problem here must not be allowed to take the whole
     * checkout down — the physical-cart path is the overwhelmingly common one
     * and is safe without the form.
     *
     * @return bool
     */
    private function isVirtualQuote(): bool
    {
        try {
            return (bool) $this->checkoutSession->getQuote()->isVirtual();
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Could not determine whether the checkout quote is virtual; '
                . 'leaving the billing address form removed.',
                ['exception' => $e]
            );

            return false;
        }
    }
}
