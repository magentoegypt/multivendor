<?php
declare(strict_types=1);

namespace MagentoEgypt\BundleExtend\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

/**
 * Refuses a bundle Special Price outside 0–100, however the product is saved.
 *
 * For bundles (core `bundle` and this module's `new_bundle`) Special Price is a
 * PERCENT of the regular price, but nothing checked the range: "75000" on the
 * Gaming Set (2026-09-25) made the storefront multiply every option by 750.
 * The admin form now validates the field (Ui\...\Modifier\Composite), but the
 * vendor panel and the REST API save products without that form, so the check
 * also runs here, on every save.
 *
 * No constructor dependencies on purpose: production runs compiled DI, and an
 * observer without constructor arguments needs no di:compile.
 */
class BundleSpecialPricePercent implements ObserverInterface
{
    private const BUNDLE_TYPES = ['bundle', BundleExtendHelper::NEW_BUNDLE_TYPE_CODE];

    public function execute(Observer $observer): void
    {
        $product = $observer->getData('product');

        if (!$product || !in_array((string) $product->getTypeId(), self::BUNDLE_TYPES, true)) {
            return;
        }

        $value = $product->getData('special_price');

        if ($value === null || $value === '' || $value === false) {
            return;
        }

        if (!is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
            throw new LocalizedException(__(
                'Special Price for a bundle product is a percent of its regular price and must be between 0 and 100 (for example 80 = 20% off). "%1" was entered.',
                $value
            ));
        }
    }
}
