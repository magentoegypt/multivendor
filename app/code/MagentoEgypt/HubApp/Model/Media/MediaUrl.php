<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Media;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use Psr\Log\LoggerInterface;

/**
 * Absolute HTTPS media URLs.
 *
 * Paths in this install's tables are stored relative to pub/media ("hero/x.jpg",
 * "mgs_brand/y.png") precisely so that the base URL is not baked in; this class
 * puts the SECURE media base in front, because the app is on HTTPS-only
 * transports (iOS ATS) and the unsecure base is http here.
 */
class MediaUrl implements MediaUrlInterface, ResetAfterRequestInterface
{
    /** @var array<int, array<string, string>> store id => url type => [unsecure, secure] bases */
    private array $bases = [];

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ImageFactory $imageHelperFactory,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function media(?string $path, int $storeId): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || $path === 'no_selection') {
            return null;
        }
        if (preg_match('~^https?://~i', $path)) {
            return $this->secure($path, $storeId);
        }
        if (str_starts_with($path, '//')) {
            return 'https:' . $path;
        }

        //  Some columns hold "/media/…" or "pub/media/…" rather than a media-relative path.
        $path = (string) preg_replace('~^/?(?:pub/)?media/~', '', $path);

        $base = $this->base($storeId, UrlInterface::URL_TYPE_MEDIA, true);
        if ($base === '') {
            return null;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @inheritDoc
     */
    public function sellerLogo(?string $value, int $storeId): ?string
    {
        return $this->inFolder($value, self::SELLER_LOGO_DIR, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function sellerBanner(?string $value, int $storeId): ?string
    {
        return $this->inFolder($value, self::SELLER_BANNER_DIR, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function productImage(
        ProductInterface $product,
        string $imageId,
        int $storeId,
        ?int $width = null,
        ?int $height = null,
        bool $keepFrame = true
    ): ?string {
        try {
            $url = $this->emulation->run($storeId, function () use ($product, $imageId, $width, $height, $keepFrame) {
                $helper = $this->imageHelperFactory->create();
                $helper->init($product, $imageId);

                //  The helper silently returns the placeholder for a product with no
                //  image of this type; the app draws its own placeholder, so say null.
                $type = (string) $helper->getType();
                $file = $type !== '' ? (string) $product->getData($type) : '';
                if ($file === '' || $file === 'no_selection') {
                    return null;
                }

                if (!$keepFrame) {
                    $helper->keepFrame(false);
                }
                if ($width !== null) {
                    $helper->resize($width, $height);
                }

                return (string) $helper->getUrl();
            });
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'HubApp: image %s for product %s unavailable: %s',
                $imageId,
                (string) $product->getId(),
                $e->getMessage()
            ));

            return null;
        }

        return is_string($url) && $url !== '' ? $this->secure($url, $storeId) : null;
    }

    /**
     * @inheritDoc
     */
    public function secure(string $url, int $storeId): string
    {
        if (stripos($url, 'http://') !== 0) {
            return $url;
        }

        foreach ([UrlInterface::URL_TYPE_MEDIA, UrlInterface::URL_TYPE_WEB, UrlInterface::URL_TYPE_LINK] as $type) {
            $unsecure = $this->base($storeId, $type, false);
            $secure = $this->base($storeId, $type, true);
            if ($unsecure !== '' && stripos($secure, 'https://') === 0 && str_starts_with($url, $unsecure)) {
                return $secure . substr($url, strlen($unsecure));
            }
        }

        //  Same host as the secure storefront: only the scheme is wrong.
        $host = parse_url($url, PHP_URL_HOST);
        $secureHost = parse_url($this->base($storeId, UrlInterface::URL_TYPE_WEB, true), PHP_URL_HOST);
        if (is_string($host) && is_string($secureHost) && strcasecmp($host, $secureHost) === 0) {
            return 'https://' . substr($url, 7);
        }

        return $url;
    }

    private function inFolder(?string $value, string $folder, int $storeId): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('~^(?:https?:)?//~i', $value) || str_starts_with(ltrim($value, '/'), $folder . '/')) {
            return $this->media($value, $storeId);
        }

        return $this->media($folder . '/' . ltrim($value, '/'), $storeId);
    }

    private function base(int $storeId, string $type, bool $secure): string
    {
        $key = $type . ($secure ? ':s' : ':u');
        if (!isset($this->bases[$storeId][$key])) {
            try {
                $this->bases[$storeId][$key] = (string) $this->storeManager->getStore($storeId)->getBaseUrl($type, $secure);
            } catch (\Throwable $e) {
                $this->logger->warning('HubApp: base URL unavailable: ' . $e->getMessage());
                $this->bases[$storeId][$key] = '';
            }
        }

        return $this->bases[$storeId][$key];
    }

    /**
     * Per-request memo only; nothing survives a request in a long-running process.
     */
    public function _resetState(): void
    {
        $this->bases = [];
    }
}
