<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use MagentoEgypt\AlgoliaVendor\Model\SellerResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Puts the seller on every product record, so Algolia can facet, filter and
 * display by seller.
 *
 * Three fields rather than one:
 *   seller_id      — stable across a rename, and what a filter should pin to
 *   seller         — what a shopper reads, and the facet label
 *   seller_url_key — lets a hit link to the seller's microsite without a
 *                    second lookup on the frontend
 */
class AddSellerData implements ObserverInterface
{
    public function __construct(private readonly SellerResolver $sellers)
    {
    }

    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('custom_data');
        $product   = $observer->getData('productObject');

        if (!$transport instanceof \Magento\Framework\DataObject || $product === null) {
            return;
        }

        $vendorId = (int) $product->getData('vendor_id');

        if ($vendorId < 1) {
            return; // Admin-created product. Correctly has no seller.
        }

        $rawName = $this->sellers->getRawName($vendorId);

        if ($rawName === null) {
            return;
        }

        /*
         * Translated, not stored raw.
         *
         * Vendor records have no store scope — one `company` column serves both
         * storefronts — so the Arabic name is a display-time __() lookup, the
         * same way profile/title.phtml and seller-line.phtml do it. That works
         * here because Algolia builds one index per store view inside
         * startEmulation($storeId), so __() resolves under the target store's
         * locale and hub_market_ar_products gets the Arabic name while
         * hub_market_en_products gets the English one.
         *
         * Overwriting the vendor record per store would be the wrong fix — see
         * the note in profile/title.phtml.
         */
        $transport->setData('seller_id', $vendorId);
        $transport->setData('seller', (string) __($rawName));

        $urlKey = $this->sellers->getUrlKey($vendorId);

        if ($urlKey !== null) {
            $transport->setData('seller_url_key', $urlKey);
        }
    }
}
