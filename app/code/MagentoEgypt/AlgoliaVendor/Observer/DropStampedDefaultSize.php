<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Keeps the stamped "55 cm" out of the Size filter, without touching products.
 *
 * The Luma sample attribute `size` had "55 cm" (option 99) as its DEFAULT, so
 * every product created with an attribute set containing it was stamped with
 * that value: perfumes, a lip gloss, a warranty service, test products — 26 of
 * the 27 indexed products carrying "55 cm", each with "55 cm" as its ONLY size.
 * The Size filter then offered "55 cm" on Pharmacy, Electronics and Furniture
 * (QA01 2026-09-25, BUG-09). The default is gone (eav_attribute.default_value
 * NULL), so new products are not stamped; the existing values are left in the
 * catalogue on purpose (the user chose a fix over a data clean-up).
 *
 * A record whose only size is that value drops `size`. Real sizes are
 * untouched: multi-size products (the Sprite Yoga Companion Kit's 55/65/75 cm
 * balls) keep "55 cm", and so do all 150 products using XS-XL or 28-36.
 * Consequence: a product genuinely made in a single 55 cm size would not show
 * in the Size filter — set its size explicitly alongside another value, or
 * remove this observer, if that ever happens.
 *
 * No constructor dependencies on purpose (compiled DI, see AddPriceRange).
 */
class DropStampedDefaultSize implements ObserverInterface
{
    private const STAMPED_VALUE = '55 cm';

    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('custom_data');

        if (!$transport instanceof \Magento\Framework\DataObject || !$transport->hasData('size')) {
            return;
        }

        $size = $transport->getData('size');
        $values = array_values(array_unique(array_map(
            static fn($value): string => trim((string) $value),
            is_array($size) ? $size : [$size]
        )));

        if ($values === [self::STAMPED_VALUE]) {
            $transport->unsetData('size');
        }
    }
}
