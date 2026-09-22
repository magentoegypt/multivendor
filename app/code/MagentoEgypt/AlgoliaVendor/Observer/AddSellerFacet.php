<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Makes the seller fields facetable in Algolia's index settings.
 *
 * A field being present on a record is not enough — Algolia refuses to facet or
 * filter on anything missing from attributesForFaceting, and the error surfaces
 * on the storefront as an empty result set rather than as a missing filter.
 *
 * Doing it here rather than only through the admin's facet JSON means a reindex
 * into a fresh index is always filterable by seller, even before anyone opens
 * Stores > Configuration. The admin facet list still controls whether the
 * filter is *displayed*; this only guarantees it is *possible*.
 */
class AddSellerFacet implements ObserverInterface
{
    /**
     * searchable() so a shopper can type into the seller filter — there are 30+
     * sellers and the list will grow. filterOnly() on the id because it is
     * never shown, only pinned to.
     */
    private const WANTED = [
        'seller'    => 'searchable(seller)',
        'seller_id' => 'filterOnly(seller_id)',
    ];

    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('index_settings');

        if (!$transport instanceof \Magento\Framework\DataObject) {
            return;
        }

        $settings = $transport->getData();
        $faceting = $settings['attributesForFaceting'] ?? [];

        if (!is_array($faceting)) {
            return;
        }

        /*
         * Algolia rejects the whole settings call if one attribute appears
         * twice, so an entry already configured in the admin wins and is left
         * exactly as the merchant wrote it — including its modifier. Entries
         * arrive as a bare name or wrapped, e.g. "searchable(seller)".
         */
        $present = [];

        foreach ($faceting as $entry) {
            if (is_string($entry) && preg_match('/^(?:[a-z]+\()?([^()]+)\)?$/i', trim($entry), $m)) {
                $present[trim($m[1])] = true;
            }
        }

        foreach (self::WANTED as $attribute => $entry) {
            if (!isset($present[$attribute])) {
                $faceting[] = $entry;
            }
        }

        $settings['attributesForFaceting'] = array_values($faceting);
        $transport->setData($settings);
    }
}
