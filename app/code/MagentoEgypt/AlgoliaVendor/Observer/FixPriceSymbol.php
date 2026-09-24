<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * English product records showed prices as "ج.م.‏500.00" in the autocomplete
 * while the English storefront shows "EGP 500.00".
 *
 * Upstream PriceManager formats with formatPrecision(..., ['locale' => en_US]),
 * which takes Magento's legacy (Zend) currency path; that data has no en_US
 * symbol for EGP and falls back to the currency's home region (ar_EG), so the
 * Arabic symbol is glued to Latin digits. Magento's own NumberFormatter path
 * (no locale option) gives "EGP 500.00" — reproduced 2026-09-24. This swaps the
 * symbol in every *_formated price of a non-Arabic store, ranges included.
 *
 * No constructor dependencies on purpose (compiled DI, see AddPriceRange).
 */
class FixPriceSymbol implements ObserverInterface
{
    private const ARABIC_SYMBOL = "/ج\\.م\\.\u{200F}?\\s*/u";

    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('custom_data');
        $product = $observer->getData('productObject');

        if (!$transport instanceof \Magento\Framework\DataObject || $product === null) {
            return;
        }

        $locale = (string) ObjectManager::getInstance()->get(ScopeConfigInterface::class)
            ->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, (int) $product->getStoreId());

        if ($locale === '' || str_starts_with($locale, 'ar')) {
            return;
        }

        $prices = $transport->getData('price');

        if (!is_array($prices)) {
            return;
        }

        foreach ($prices as $currency => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $key => $value) {
                if (is_string($value) && str_ends_with((string) $key, '_formated')) {
                    $prices[$currency][$key] = preg_replace(self::ARABIC_SYMBOL, $currency . ' ', $value);
                }
            }
        }

        $transport->setData('price', $prices);
    }
}
