<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\Quote\Item;
use MagentoEgypt\HomeSections\ViewModel\VendorNames;

/**
 * Groups cart line items by the vendor selling them.
 *
 * The Figma cart does not render one flat list — it renders one card PER SELLER,
 * each opening with the seller's name. On a marketplace that is the difference
 * between "three things in a basket" and "three things from three different
 * shops, each of which will ship and invoice separately". It is the single most
 * marketplace-specific thing on the page, so it is worth the block.
 *
 * GROUPING RULE
 * -------------
 * Items keep their quote order, and a group is opened the first time a vendor is
 * seen. Two items from the same seller therefore share a card even if something
 * from another seller sits between them in the quote — which is what a shopper
 * means by "who am I buying this from", and what the reference draws.
 *
 * `catalog_product_entity.vendor_id` is a static column and is already loaded on
 * the quote item's product, so this costs no extra query per line. The id ->
 * name resolution reuses HomeSections' VendorNames, which fetches the whole
 * 25-row vendor table once per request; that map is already warm on most pages
 * because the product cards use it.
 *
 * ADMIN-OWNED PRODUCTS
 * --------------------
 * 91 of the ~2300 products here carry vendor_id 0 — created in admin, not by a
 * seller. They genuinely have no vendor to name, so they group together and the
 * template omits the header strip rather than inventing a seller. Naming the
 * store instead was the alternative and it reads worse: the store's frontend
 * name on this install is "الموقع الرئيسي للمتجر" ("Main Website Store").
 */
class CartVendors implements ArgumentInterface
{
    /**
     * Storefront path segment the vendor shop pages hang off, e.g. `shop` in
     * https://host/en/shop/loly/. Vnecoms\VendorsPage\Helper\Data builds the same
     * string, but type-hinting that helper would make the cart page fail to
     * compile if the marketplace modules were ever removed — and the whole of it
     * is one config read plus a URL join, verified to produce the byte-identical
     * result.
     */
    private const XML_PATH_VENDOR_PAGE_URL_KEY = 'vendors/vendorspage/url_key';

    public function __construct(
        private readonly VendorNames $vendorNames,
        private readonly UrlInterface $url,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param  Item[] $items
     * @return array<int, array{vendor_id:int, name:?string, url:?string, items:Item[]}>
     */
    public function getGroups(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            $product  = $item->getProduct();
            $vendorId = $product ? (int) $product->getData('vendor_id') : 0;

            if (!isset($groups[$vendorId])) {
                $name = $this->vendorNames->getName($vendorId);
                $groups[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'name'      => $name,
                    //  No link without a name: a bare seller URL with nothing to
                    //  click is worse than plain text.
                    'url'       => $name !== null ? $this->getVendorUrl($vendorId) : null,
                    'items'     => [],
                ];
            }

            $groups[$vendorId]['items'][] = $item;
        }

        return array_values($groups);
    }

    /**
     * Shop page for a vendor, or null when one cannot be built.
     */
    public function getVendorUrl(mixed $vendorId): ?string
    {
        $code = $this->vendorNames->getUrlKey($vendorId);
        if ($code === null || $code === '') {
            return null;
        }

        $base = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_VENDOR_PAGE_URL_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
        if ($base === '') {
            return null;
        }

        return $this->url->getUrl($base . '/' . $code);
    }
}
