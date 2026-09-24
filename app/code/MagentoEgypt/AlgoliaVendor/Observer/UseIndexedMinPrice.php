<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Products whose own `price` attribute is 0 were indexed at EGP 0 (2026-09-24):
 * the Magento-Egypt `new_bundle` type ("Gaming Set", really 4.25-1,275) and
 * downloadable products whose links carry the price ("Luma Yoga For Life",
 * 10-33). The extension only computes a min/max for types it knows, so these
 * showed "EGP 0.00" in the autocomplete, sorted first by price, fell out of
 * price filters and were caught by the Recommend rule that drops zero prices.
 *
 * Magento already knows the real range: catalog_product_index_price.min_price /
 * max_price (the same figures the storefront's "From ... To ..." uses). When the
 * record's default price is 0 and the index has a positive minimum, write the
 * record the way the extension writes a bundle: default = min, default_max = max,
 * default_formated = "min - max" (or just min when they are equal).
 *
 * Runs before AddPriceRange (band from the corrected price) and FixPriceSymbol
 * (English symbol), which is their order in etc/events.xml.
 *
 * No constructor dependencies on purpose (compiled DI, see AddPriceRange).
 */
class UseIndexedMinPrice implements ObserverInterface
{
    private const NOT_LOGGED_IN = 0;

    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('custom_data');
        $product = $observer->getData('productObject');

        if (!$transport instanceof \Magento\Framework\DataObject || $product === null) {
            return;
        }

        $prices = $transport->getData('price');

        if (!is_array($prices) || !$this->allDefaultsZero($prices)) {
            return;
        }

        $om = ObjectManager::getInstance();
        $store = $om->get(StoreManagerInterface::class)->getStore((int) $product->getStoreId());
        $connection = $om->get(ResourceConnection::class)->getConnection();
        $range = $connection->fetchRow(
            $connection->select()
                ->from($connection->getTableName('catalog_product_index_price'), ['min_price', 'max_price'])
                ->where('entity_id = ?', (int) $product->getId())
                ->where('website_id = ?', (int) $store->getWebsiteId())
                ->where('customer_group_id = ?', self::NOT_LOGGED_IN)
        );

        if (!$range || (float) $range['min_price'] <= 0) {
            return;
        }

        $priceCurrency = $om->get(PriceCurrencyInterface::class);
        $locale = (string) $om->get(ScopeConfigInterface::class)
            ->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, (int) $store->getId());

        foreach ($prices as $currencyCode => $values) {
            if (!is_array($values)) {
                continue;
            }

            $min = $priceCurrency->convert((float) $range['min_price'], $store, $currencyCode);
            $max = $priceCurrency->convert((float) $range['max_price'], $store, $currencyCode);
            $currency = $priceCurrency->getCurrency($store, $currencyCode);
            $format = function (float $amount) use ($currency, $locale): string {
                return $currency->formatPrecision(
                    $amount,
                    PriceCurrencyInterface::DEFAULT_PRECISION,
                    $locale !== '' ? ['locale' => $locale] : [],
                    false
                );
            };

            $prices[$currencyCode]['default'] = $min;
            $prices[$currencyCode]['default_formated'] = $max > $min
                ? $format($min) . ' - ' . $format($max)
                : $format($min);

            if ($max > $min) {
                $prices[$currencyCode]['default_max'] = $max;
            }

            // A "was" price of 0 is not a discount.
            unset($prices[$currencyCode]['default_original_formated']);
        }

        $transport->setData('price', $prices);
    }

    private function allDefaultsZero(array $prices): bool
    {
        $seen = false;

        foreach ($prices as $values) {
            if (!is_array($values) || !isset($values['default']) || !is_numeric($values['default'])) {
                continue;
            }

            if ((float) $values['default'] > 0) {
                return false;
            }

            $seen = true;
        }

        return $seen;
    }
}
