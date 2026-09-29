<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Api;

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Absolute HTTPS URLs for media the app shows.
 *
 * HTTPS always: iOS App Transport Security refuses plain http images, and the
 * store's unsecure media base is http on some environments.
 */
interface MediaUrlInterface
{
    /** Seller logo values in ves_vendor_config are relative to this media folder (VendorsConfig di.xml). */
    public const SELLER_LOGO_DIR = 'ves_vendors/logo';

    /** Seller banner values in ves_vendor_config are relative to this media folder. */
    public const SELLER_BANNER_DIR = 'ves_vendors/banner';

    /**
     * URL for a path stored relative to pub/media (hero/…, mgs_brand/…).
     *
     * An absolute http(s) value passes through (http is upgraded to https when
     * it points at this store's own media host). Empty input gives null.
     */
    public function media(?string $path, int $storeId): ?string;

    /**
     * Seller logo URL from the raw ves_vendor_config value, or null.
     */
    public function sellerLogo(?string $value, int $storeId): ?string;

    /**
     * Seller banner URL from the raw ves_vendor_config value, or null.
     */
    public function sellerBanner(?string $value, int $storeId): ?string;

    /**
     * Resized catalog image through Magento\Catalog\Helper\Image, as the website renders it.
     *
     * Runs inside storefront emulation (the image ids live in the theme's view.xml).
     * $keepFrame false scales to fit without the white padding the website
     * strips from its bundle cards. Returns null when the product has no image
     * and the placeholder would be all the app gets.
     */
    public function productImage(
        ProductInterface $product,
        string $imageId,
        int $storeId,
        ?int $width = null,
        ?int $height = null,
        bool $keepFrame = true
    ): ?string;

    /**
     * Upgrade an absolute URL on this store's media or base host to https.
     */
    public function secure(string $url, int $storeId): string;
}
