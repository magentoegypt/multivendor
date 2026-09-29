<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Vendors;

use MagentoEgypt\HomeSections\ViewModel\VendorNames;

/**
 * Never an empty seller name, and never a seller card for a seller that does not exist.
 *
 * 1. afterGetVendorStoreName (Vnecoms\Vendors\Helper\Data). Vnecoms names a seller only by the
 *    seller-panel setting general/store_information/name, which 27 approved sellers never filled
 *    in, with no fallback. The shop "items" page was titled "'s items" / "عناصر", the product
 *    page's seller logo had an empty alt, and the Featured Stores rail fell back to URL keys.
 *    A blank or non-name value ("0", ".") now falls back to VendorNames::getName(): the company,
 *    else the seller code, the same name the product cards show.
 *
 * 2. afterGetVendor (Vnecoms\Vendors\Block\Profile). For a product whose seller was deleted
 *    (seller 18 left 22 products behind) Vnecoms loads an EMPTY vendor model rather than none,
 *    so its own "no vendor, no card" check in _toHtml() never fired: the product page printed a
 *    seller card with an empty name and a "Visit Store" link to /shop/ (404). An unsaved model
 *    is returned as null, so every profile block (card, title, logo, stats) renders nothing, the
 *    same as for the store's own products.
 */
class SellerNameFallback
{
    public function __construct(private readonly VendorNames $vendorNames)
    {
    }

    /**
     * @param \Vnecoms\Vendors\Helper\Data $subject
     * @param mixed $result
     * @param mixed $vendorId
     * @return mixed
     */
    public function afterGetVendorStoreName($subject, $result, $vendorId = null)
    {
        if (VendorNames::isName(is_string($result) ? trim($result) : null)) {
            return $result;
        }

        return $this->vendorNames->getName($vendorId) ?? $result;
    }

    /**
     * @param \Vnecoms\Vendors\Block\Profile $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetVendor($subject, $result)
    {
        if ($result instanceof \Magento\Framework\Model\AbstractModel && !$result->getId()) {
            return null;
        }

        return $result;
    }
}
