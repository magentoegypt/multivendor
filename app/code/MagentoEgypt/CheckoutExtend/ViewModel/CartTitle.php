<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\ViewModel;

use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

/**
 * Supplies the "(3 items)" suffix for the cart page <h1>, matching the Figma
 * reference heading "Shopping Cart (3 items)".
 *
 * The count comes from Checkout\Helper\Cart::getSummaryCount() — the SAME source
 * the minicart badge uses — so the heading and the header badge can never
 * disagree, and it honours the store's "display item quantities vs unique items"
 * setting for free.
 *
 * Safe to compute server-side here: the cart page is uncacheable (its item block
 * is cacheable="false"), and Theme\Block\Html\Title declares no cache lifetime,
 * so the heading is not block_html cached either. Both were verified before this
 * was written — a cached count would be worse than no count.
 */
class CartTitle implements ArgumentInterface
{
    public function __construct(
        private readonly CartHelper $cartHelper,
        private readonly ResolverInterface $localeResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The count as digits in the store's own numbering system.
     *
     * ar_SA renders Arabic-Indic digits ("١"), which is what the rest of this
     * storefront already shows — prices ("٣٤ ج.م.") and the minicart badge both
     * do. Passing the raw PHP int into the phrase instead produced a Latin "1"
     * sitting inside an otherwise Arabic heading.
     */
    private function formatCount(int $count): string
    {
        $formatter = new \NumberFormatter($this->localeResolver->getLocale(), \NumberFormatter::DECIMAL);

        return $formatter->format($count) ?: (string) $count;
    }

    /**
     * Number of items as the minicart badge counts them.
     */
    public function getItemCount(): int
    {
        try {
            return (int) $this->cartHelper->getSummaryCount();
        } catch (\Throwable $e) {
            // A heading must never take the cart page down.
            $this->logger->warning('CheckoutExtend: cart count unavailable: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Localised "(N items)" suffix, or '' when the cart is empty — the empty-cart
     * page keeps the bare "Shopping Cart" heading, as the Figma reference does.
     *
     * Two plural forms (one / other) rather than Arabic's full four (one, two,
     * few, many). That is a deliberate match to core Magento, whose own Arabic
     * pack ships exactly this split ("منتج في سلة التسوق" / "منتجات في سلة التسوق"):
     * Magento's i18n layer has no plural categories, so four forms cannot be
     * expressed in a CSV. Being consistent with the rest of the store beats
     * inventing a scheme translators cannot edit.
     */
    public function getCountSuffix(): string
    {
        $count = $this->getItemCount();
        if ($count < 1) {
            return '';
        }

        $digits = $this->formatCount($count);

        return (string) ($count === 1 ? __('(%1 item)', $digits) : __('(%1 items)', $digits));
    }
}
