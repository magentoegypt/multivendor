<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Offer;

use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubAppVendors\Model\Store\DispatchTimeReader;

/**
 * Other sellers' offers on a product (HmProductOffer): the website's "Sold by N
 * other sellers" (Vnecoms_VendorsPriceComparison, see FamilyReader).
 *
 * The offers of a product are the other members of its family:
 *   - on the main product, every copy — what the website's product page lists;
 *   - on a copy (reached from the list), the main product and the other copies.
 *     The website means the same (MagentoEgypt_VendorExtend's
 *     PriceComparisonLoadProduct asks for "the main product OR a copy of it"),
 *     but its filter array is nested one level too deep, so Magento keeps only
 *     the first condition and a copy's page lists the main product alone. The
 *     app lists the whole family either way.
 * Each is kept only when the website would list it (OfferGate, then an approved
 * seller of their own and stock), and they come cheapest first, as the
 * website's table sorts them (final price, then oldest product).
 *
 * Batched for every product of a response: two family queries, the gate, one
 * seller-summary call, one product collection, one dispatch-time call. The
 * result is kept for the request, so hm_offer_count and hm_other_offers of the
 * same products cost one computation.
 */
class OfferFinder implements ResetAfterRequestInterface
{
    public const IN_STOCK = 'IN_STOCK';

    /** @var array<int, array<int, array{offers: array<int, array<string, mixed>>, tags: string[]}>> store id => product id => result */
    private array $known = [];

    public function __construct(
        private readonly FamilyReader $families,
        private readonly OfferGate $gate,
        private readonly OfferProducts $products,
        private readonly SellerSummaryProviderInterface $sellers,
        private readonly DispatchTimeReader $dispatchTimes
    ) {
    }

    /**
     * @param int[] $productIds
     * @return array<int, array<int, array<string, mixed>>> product id => HmProductOffer values, cheapest first
     */
    public function offers(array $productIds, int $storeId, ContextInterface $context): array
    {
        $ids = FamilyReader::normalise($productIds);
        $this->resolve($ids, $storeId, $context);

        $out = [];
        foreach ($ids as $productId) {
            $out[$productId] = $this->known[$storeId][$productId]['offers'];
        }

        return $out;
    }

    /**
     * HTTP cache tags of the answers for $productIds: every member of their
     * families (a copy's save, its stock or approval, changes the list) and every
     * offering seller. Call offers() first.
     *
     * @param int[] $productIds
     * @return string[]
     */
    public function cacheTags(array $productIds, int $storeId): array
    {
        $tags = [];
        foreach (FamilyReader::normalise($productIds) as $productId) {
            foreach ($this->known[$storeId][$productId]['tags'] ?? [] as $tag) {
                $tags[$tag] = $tag;
            }
        }

        return array_values($tags);
    }

    /**
     * @param int[] $ids
     */
    private function resolve(array $ids, int $storeId, ContextInterface $context): void
    {
        $wanted = array_values(array_filter($ids, fn (int $id): bool => !isset($this->known[$storeId][$id])));
        if (!$wanted) {
            return;
        }

        $families = $this->families->families($wanted);
        $candidates = [];
        foreach ($families as $members) {
            if (count($members) > 1) {
                foreach ($members as $member) {
                    $candidates[$member] = $member;
                }
            }
        }
        $built = $candidates ? $this->build(array_values($candidates), $storeId, $context) : [];

        foreach ($wanted as $productId) {
            $members = $families[$productId] ?? [$productId];
            $offers = [];
            foreach ($members as $member) {
                if ($member !== $productId && isset($built[$member])) {
                    $offers[] = $built[$member];
                }
            }
            $offers = self::cheapestFirst($offers);

            $tags = [];
            if (count($members) > 1) {
                foreach ($members as $member) {
                    $tags[] = Tags::product($member);
                }
                foreach ($offers as $offer) {
                    $vendorId = (int) ($offer['seller']['vendor_entity_id'] ?? 0);
                    if ($vendorId > 0) {
                        $tags[] = Tags::vendor($vendorId);
                    }
                }
            }

            $this->known[$storeId][$productId] = [
                'offers' => array_map(static fn (array $offer): array => self::withoutKeys($offer), $offers),
                'tags' => array_values(array_unique($tags)),
            ];
        }
    }

    /**
     * HmProductOffer values of the family members the website would list.
     *
     * @param int[] $candidates
     * @return array<int, array<string, mixed>> by product id
     */
    private function build(array $candidates, int $storeId, ContextInterface $context): array
    {
        $passing = $this->gate->passing($candidates, $storeId);
        if (!$passing) {
            return [];
        }

        $vendorIds = array_values(array_unique(array_column($passing, 'vendor_id')));
        $summaries = $this->sellers->getByVendorIds($vendorIds, $storeId);
        $sellers = [];
        foreach ($passing as $productId => $row) {
            $summary = $summaries[$row['vendor_id']] ?? null;
            //  An approved seller of their own: a pending, disabled or deleted seller
            //  (the latter shows as Hub Market) never offers anything.
            if (is_array($summary) && empty($summary['is_marketplace'])) {
                $sellers[$productId] = $summary;
            }
        }
        if (!$sellers) {
            return [];
        }

        $products = array_filter(
            $this->products->load(array_keys($sellers), $context),
            static fn (array $product): bool => !empty($product['in_stock'])
        );
        if (!$products) {
            return [];
        }

        $dispatch = $this->dispatchTimes->forVendors(
            array_values(array_unique(array_map(
                static fn (int $productId): int => (int) $passing[$productId]['vendor_id'],
                array_keys($products)
            ))),
            $storeId
        );

        $out = [];
        foreach ($products as $productId => $product) {
            $vendorId = (int) $passing[$productId]['vendor_id'];
            $out[$productId] = [
                'uid' => $product['uid'],
                'sku' => $product['sku'],
                'url_key' => $product['url_key'],
                'type_id' => $product['type_id'],
                'seller' => $sellers[$productId],
                'price' => $product['price'],
                'regular_price' => $product['regular_price'],
                'stock_status' => self::IN_STOCK,
                'dispatch_time' => $dispatch[$vendorId] ?? null,
                '_product_id' => (int) $productId,
            ];
        }

        return $out;
    }

    /**
     * The website's order: final price ascending; the older product first on a tie.
     *
     * @param array<int, array<string, mixed>> $offers
     * @return array<int, array<string, mixed>>
     */
    public static function cheapestFirst(array $offers): array
    {
        usort($offers, static function (array $a, array $b): int {
            $byPrice = (float) ($a['price']['value'] ?? 0) <=> (float) ($b['price']['value'] ?? 0);

            return $byPrice !== 0 ? $byPrice : ((int) ($a['_product_id'] ?? 0) <=> (int) ($b['_product_id'] ?? 0));
        });

        return array_values($offers);
    }

    /**
     * @param array<string, mixed> $offer
     * @return array<string, mixed> the HmProductOffer fields only
     */
    private static function withoutKeys(array $offer): array
    {
        unset($offer['_product_id']);

        return $offer;
    }

    /**
     * Per-request memo only.
     */
    public function _resetState(): void
    {
        $this->known = [];
    }
}
