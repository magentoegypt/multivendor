<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Moves the billing address form out of the payment step into a step of its own.
 *
 * WHY A PLUGIN AND NOT LAYOUT XML
 * -------------------------------
 * The form is not declared anywhere in checkout_index_index.xml. LayoutProcessor
 * builds it in PHP and merges it into the payment component at runtime — either
 * one shared form under `afterMethods`, or one per method under `payments-list`,
 * depending on checkout/options/display_billing_address_on. Nothing in jsLayout
 * can disable or move a node that jsLayout never declared, so it is done after
 * the fact.
 *
 * WHAT THIS USED TO DO, AND WHY IT CHANGED (2026-09-22)
 * ----------------------------------------------------
 * It used to DELETE the form. The reasoning was sound for a physical cart: the
 * address is still set by the path Magento uses when "My billing and shipping
 * address are the same" stays ticked — shipping.js's setShippingInformation()
 * clears quote.billingAddress() and lets checkoutDataResolver fall through to
 * selectBillingAddress(quote.shippingAddress()). On a single-shipping-address
 * storefront the form only offered the shopper a chance to disagree with
 * themselves.
 *
 * All of that depends on there BEING a shipping address. A cart of only virtual
 * or downloadable items has no shipping step, so setShippingInformation() never
 * runs and the fallback never fires. Deleting the form there removed the ONLY
 * place a shopper could enter the one address the order would ever carry.
 * Reproduced as a guest with a virtual product: an e-mail field, a security
 * notice, a disabled Review Order button, zero address inputs in the DOM and no
 * payment methods — checkout was a dead end.
 *
 * WHY THERE IS NO isVirtual() BRANCH IN HERE
 * ------------------------------------------
 * The first fix branched in PHP on checkoutSession->getQuote()->isVirtual().
 * That is not reliable at layout-processing time: an empty or not-yet-populated
 * quote reports isVirtual() === FALSE, and a wrong answer meant deleting the
 * form. It was caught in testing — one run in a sequence rendered a virtual cart
 * with no address form anywhere, while the browser's own checkoutConfig for that
 * same page said is_virtual = 1. An intermittent route back to the original
 * defect is worse than the defect, because it will not reproduce on demand.
 *
 * So PHP no longer decides anything. The form is ALWAYS moved and NEVER deleted,
 * which makes a "no address form at all" layout unreachable by construction.
 * Whether the step is SHOWN is decided in the browser by billing-step.js, from
 * `quote.isVirtual()` — the same flag Magento itself branches on, read from the
 * checkoutConfig of the very request that rendered the page:
 *
 *   virtual    the step registers with the navigator -> Billing, Payment, Review
 *   physical   it never registers, so the step stays hidden and the billing
 *              address keeps coming from the shipping address exactly as before
 *
 * A present-but-hidden form on a physical cart is also closer to stock Magento,
 * which shows it there by default, and unlike a deletion it cannot cost the
 * order its address.
 *
 * Only the shared form is moved. Under display_billing_address_on = payment
 * method Magento builds one form per method under `payments-list` instead; there
 * is no single node to lift, so that shape is left exactly as Magento built it
 * and the step renders nothing. That setting is not in use on this store.
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
        return $this->moveBillingFormToOwnStep($jsLayout);
    }

    /**
     * Lift Magento's own billing-address-form node into a step ahead of Payment.
     *
     * THE NODE IS MOVED, NOT REBUILT. It travels with its `billingAddressList`
     * child, and its one external-looking link,
     * `billingAddressListProvider: '${$.name}.billingAddressList'`, resolves
     * against the component's OWN name — so reparenting does not break it.
     * Rebuilding the form would mean owning address validation and
     * selectBillingAddress() by hand, which is how a checkout starts taking
     * orders with no usable billing address.
     *
     * sortOrder 1 is the slot the shipping step occupies. Safe to share: the two
     * are never both on screen — Magento hides shipping on a virtual cart, and
     * billing-step.js only shows this one on a virtual cart.
     *
     * @param  array $jsLayout
     * @return array
     */
    private function moveBillingFormToOwnStep(array $jsLayout): array
    {
        if (!isset(
            $jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']['afterMethods']['children']
                ['billing-address-form']
        )) {
            return $jsLayout;
        }

        $steps = &$jsLayout['components']['checkout']['children']['steps']['children'];

        $form = $steps['billing-step']['children']['payment']['children']
            ['afterMethods']['children']['billing-address-form'];

        unset(
            $steps['billing-step']['children']['payment']['children']
                ['afterMethods']['children']['billing-address-form']
        );

        $steps['hm-billing-step'] = [
            'component' => 'MagentoEgypt_CheckoutExtend/js/view/billing-step',
            'sortOrder' => '1',
            'config' => [
                'template' => 'MagentoEgypt_CheckoutExtend/billing-step',
            ],
            'children' => [
                'billing-address-form' => $form,
            ],
        ];

        unset($steps);

        return $jsLayout;
    }
}
