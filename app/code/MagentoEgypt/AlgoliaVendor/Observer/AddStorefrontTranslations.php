<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Adds strings to `algoliaConfig.translations` that Algolia's own templates
 * hard-code in English.
 *
 * The one that matters: view/frontend/web/js/template/autocomplete/products.js renders the
 * category line of every product suggestion as
 *
 *     html`<span>in ${highlight}</span>`
 *
 * — a literal "in", not a translation key — so the Arabic storefront showed
 * "in نسائي, رجالي" under every product. Block\Configuration builds
 * `translations` with __() and then dispatches this event, so a string added
 * here is resolved on the storefront's own request, in its own locale. The
 * theme's products.js override reads it back as `translations.hmIn`.
 *
 * Why not $t('in') in the JS instead: the JS dictionary resolves phrases
 * without context, and a bare "in" is claimed store-wide as "بوصة" (inch) by
 * VendorExtend's dimension labels. Resolving it here keeps the lookup on the
 * same path every other Algolia label already takes.
 *
 * No constructor dependencies on purpose — see the sibling observers: this
 * module ships to production without always getting a di:compile.
 */
class AddStorefrontTranslations implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('configuration');

        if (!$transport instanceof DataObject) {
            return;
        }

        $translations = $transport->getData('translations');

        if (!is_array($translations)) {
            return;
        }

        $translations['hmIn'] = (string) __('in');
        $transport->setData('translations', $translations);
    }
}
