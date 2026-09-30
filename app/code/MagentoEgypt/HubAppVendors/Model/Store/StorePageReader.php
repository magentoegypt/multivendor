<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Model\Seller\VendorConfigReader;
use Psr\Log\LoggerInterface;

/**
 * The store page fields of HmStore beyond the card, as /shop/<code> shows them.
 *
 * - banner_url: the seller's page banner; when the seller has none, or its file
 *   is gone, the admin's default banner (vendors/vendorspage/default_banner),
 *   exactly like Vnecoms\VendorsPage\Block\Home\Banner.
 * - about / shipping / refund: the seller's own HTML, each shown only when the
 *   admin switch the website's blocks obey is on (VendorsPage Helper
 *   canShowSeller*(), read at default scope as the helper does). Empty = null.
 * - short_description: the seller's "Store Information" text, when the admin
 *   shows short descriptions (vendors/profile/show_short_description).
 *
 * One ves_vendor_config query for all five settings.
 */
class StorePageReader
{
    private const SHOW_ABOUT = 'vendors/vendorspage/show_about';
    private const SHOW_SHIPPING = 'vendors/vendorspage/show_shipping';
    private const SHOW_REFUND = 'vendors/vendorspage/show_refund';
    private const SHOW_SHORT_DESCRIPTION = 'vendors/profile/show_short_description';
    private const DEFAULT_BANNER = 'vendors/vendorspage/default_banner';

    public function __construct(
        private readonly VendorConfigReader $vendorConfig,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly MediaUrlInterface $mediaUrl,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{banner_url: string|null, short_description: string|null, about_html: string|null,
     *               shipping_policy_html: string|null, refund_policy_html: string|null}
     */
    public function read(int $vendorId, int $storeId): array
    {
        $settings = $this->vendorConfig->read(
            [$vendorId],
            [
                VendorConfigReader::BANNER,
                VendorConfigReader::SHORT_DESCRIPTION,
                VendorConfigReader::ABOUT,
                VendorConfigReader::SHIPPING_POLICY,
                VendorConfigReader::REFUND_POLICY,
            ],
            $storeId
        )[$vendorId] ?? [];

        return [
            'banner_url' => $this->banner($settings[VendorConfigReader::BANNER] ?? null, $storeId),
            'short_description' => $this->flag(self::SHOW_SHORT_DESCRIPTION)
                ? self::text($settings[VendorConfigReader::SHORT_DESCRIPTION] ?? null)
                : null,
            'about_html' => $this->flag(self::SHOW_ABOUT)
                ? self::text($settings[VendorConfigReader::ABOUT] ?? null)
                : null,
            'shipping_policy_html' => $this->flag(self::SHOW_SHIPPING)
                ? self::text($settings[VendorConfigReader::SHIPPING_POLICY] ?? null)
                : null,
            'refund_policy_html' => $this->flag(self::SHOW_REFUND)
                ? self::text($settings[VendorConfigReader::REFUND_POLICY] ?? null)
                : null,
        ];
    }

    private function banner(?string $file, int $storeId): ?string
    {
        foreach ([$file, (string) $this->scopeConfig->getValue(self::DEFAULT_BANNER)] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && $this->exists(MediaUrlInterface::SELLER_BANNER_DIR . '/' . ltrim($candidate, '/'))) {
                return $this->mediaUrl->sellerBanner($candidate, $storeId);
            }
        }

        return null;
    }

    private function exists(string $mediaPath): bool
    {
        try {
            return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->isFile($mediaPath);
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller banner check failed: ' . $e->getMessage());

            return false;
        }
    }

    private function flag(string $path): bool
    {
        //  Default scope, as VendorsPage\Helper\Data reads these switches.
        return (bool) $this->scopeConfig->getValue($path);
    }

    private static function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
