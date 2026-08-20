<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Takes the billing address form out of the payment step.
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
 * WHY THIS DOES NOT LOSE THE BILLING ADDRESS
 * ------------------------------------------
 * Only the UI goes. The address is still set, by the path Magento already uses
 * when a shopper leaves "My billing and shipping address are the same" ticked:
 * shipping.js's setShippingInformation() clears quote.billingAddress() and calls
 * checkoutDataResolver.resolveBillingAddress(), whose applyBillingAddress() falls
 * through to selectBillingAddress(quote.shippingAddress()). The order therefore
 * still carries a billing address, and it is the shipping one.
 *
 * This is a single-shipping-address storefront with no separate billing flow, so
 * the form only ever offered the shopper the chance to disagree with themselves.
 */
class RemoveBillingAddress
{
    /**
     * @param  LayoutProcessor $subject
     * @param  array           $jsLayout
     * @return array
     */
    public function afterProcess(LayoutProcessor $subject, $jsLayout)
    {
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
}
