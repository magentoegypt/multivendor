<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Model\Seller\ListableProducts;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;
use MagentoEgypt\HubApp\Model\Seller\SellerName;
use MagentoEgypt\HubApp\Model\Seller\SellerRatings;
use MagentoEgypt\HubApp\Model\Seller\VendorConfigReader;

/**
 * HmStoreCard values, built in two steps so a list only pays for what it shows.
 *
 *   summaries()  what filtering and sorting need, for every candidate seller:
 *                name, rating, reviews, listable products, featured flag, join date;
 *   complete()   what only the shown page needs: logo, dispatch time, link.
 *
 * Every step is batched over the sellers (see the readers); the seller data is
 * the same the website's cards use (HomeSections NewStores / VendorMeta /
 * VendorNames), through the core seller services the "sold by" summaries share.
 */
class StoreCards
{
    /** Vnecoms seller attribute behind "Show on Home" (the Featured Stores widget's fallback). */
    public const FEATURED_ATTRIBUTE = 'is_home';

    public function __construct(
        private readonly SellerDirectory $directory,
        private readonly SellerRatings $ratings,
        private readonly ListableProducts $listableProducts,
        private readonly VendorConfigReader $vendorConfig,
        private readonly VendorAttributeReader $attributes,
        private readonly DispatchTimeReader $dispatchTimes,
        private readonly MediaUrlInterface $mediaUrl,
        private readonly LinkResolverInterface $linkResolver
    ) {
    }

    /**
     * Sortable card fields of APPROVED sellers; other ids are dropped.
     *
     * @param int[] $vendorIds
     * @return array<int, array<string, mixed>> vendor id => partial card, in the order given
     */
    public function summaries(array $vendorIds, int $storeId): array
    {
        $approved = [];
        foreach ($vendorIds as $vendorId) {
            $vendor = $this->directory->getApproved((int) $vendorId);
            if ($vendor !== null) {
                $approved[$vendor['id']] = $vendor;
            }
        }
        if (!$approved) {
            return [];
        }

        $ids = array_keys($approved);
        $names = $this->directory->names($ids, $storeId);
        $ratings = $this->ratings->forVendors($ids);
        $counts = $this->listableProducts->counts($ids, $storeId);
        $featured = $this->attributes->values(self::FEATURED_ATTRIBUTE, $ids);

        $out = [];
        foreach ($approved as $vendorId => $vendor) {
            $out[$vendorId] = [
                'code' => $vendor['code'],
                'vendor_entity_id' => $vendorId,
                'name' => $names[$vendorId] ?? SellerName::source($vendor['company'], $vendor['code']),
                'rating' => $ratings[$vendorId]['rating'] ?? null,
                'review_count' => (int) ($ratings[$vendorId]['review_count'] ?? 0),
                'product_count' => (int) ($counts[$vendorId] ?? 0),
                'is_featured' => (int) ($featured[$vendorId] ?? 0) === 1,
                'joined_at' => self::isoUtc($vendor['created_at']),
            ];
        }

        return $out;
    }

    /**
     * Adds logo_url, dispatch_time and link to partial cards.
     *
     * @param array<int|string, array<string, mixed>> $cards from summaries()
     * @return array<int, array<string, mixed>> complete HmStoreCard values, order kept
     */
    public function complete(array $cards, int $storeId): array
    {
        $cards = array_values($cards);
        if (!$cards) {
            return [];
        }

        $ids = array_map(static fn (array $card): int => (int) $card['vendor_entity_id'], $cards);
        $logos = $this->vendorConfig->read($ids, [VendorConfigReader::LOGO], $storeId);
        $dispatch = $this->dispatchTimes->forVendors($ids, $storeId);

        foreach ($cards as &$card) {
            $vendorId = (int) $card['vendor_entity_id'];
            $card['logo_url'] = $this->mediaUrl->sellerLogo($logos[$vendorId][VendorConfigReader::LOGO] ?? null, $storeId);
            $card['dispatch_time'] = $dispatch[$vendorId] ?? null;
            $card['link'] = $this->linkResolver->store((string) $card['code'], $storeId);
        }
        unset($card);

        return $cards;
    }

    /**
     * ves_vendor_entity.created_at (a TIMESTAMP, read in UTC: Magento's connection runs at +00:00)
     * as ISO-8601 UTC.
     */
    public static function isoUtc(string $value): string
    {
        $value = trim($value);
        if ($value !== '' && !str_starts_with($value, '0000-00-00')) {
            try {
                return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format('Y-m-d\TH:i:s\Z');
            } catch (\Exception $e) {
                //  Unparseable: fall through to the epoch rather than a null in a non-null field.
            }
        }

        return '1970-01-01T00:00:00Z';
    }
}
