<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Seller\ListableProducts;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;
use MagentoEgypt\HubApp\Model\Seller\SellerRatings;
use MagentoEgypt\HubAppVendors\Model\Resolver\Identity\StoreReviewsIdentity;
use MagentoEgypt\HubAppVendors\Model\Store\ReviewProducts;
use MagentoEgypt\HubAppVendors\Model\Store\SellerReviews;
use MagentoEgypt\HubAppVendors\Model\Store\StoreReviewsQuery;
use PHPUnit\Framework\TestCase;

/**
 * hmStoreReviews: the card's rating as the summary, the store view's reviews as the items.
 */
final class StoreReviewsQueryTest extends TestCase
{
    private const MIA = ['id' => 7, 'code' => 'MIA', 'company' => 'MIA CO', 'status' => 2, 'created_at' => ''];

    public function testAPageCarriesTheCardRatingAndTheStoreViewsReviews(): void
    {
        $reviews = $this->createMock(SellerReviews::class);
        $reviews->expects(self::once())->method('page')->with(7, 2, 2, 1)->willReturn([
            'total' => 3,
            'rows' => [
                [
                    'review_id' => 41, 'product_id' => 301, 'created_at' => '2026-09-20 08:15:00',
                    'nickname' => 'Sara', 'title' => 'Great sofa', 'detail' => 'Comfortable and well made.',
                ],
                [
                    'review_id' => 40, 'product_id' => 302, 'created_at' => '2026-09-18 10:00:00',
                    'nickname' => 'Omar', 'title' => '', 'detail' => '',
                ],
            ],
        ]);
        $reviews->method('ratings')->with([41, 40])->willReturn([41 => 4.5]);

        $products = $this->createMock(ReviewProducts::class);
        $products->expects(self::once())->method('forPage')->with([301, 302], [301, 305], 2)->willReturn([
            301 => ['name' => 'Corner Sofa Bed', 'url_key' => 'corner-sofa-bed', 'thumbnail_url' => 'https://m/t.jpg'],
        ]);

        $page = $this->query($reviews, $products)->execute('mia', 2, 1, 2);

        self::assertSame(7, $page['vendor_entity_id']);
        self::assertSame(['rating' => 4.8, 'review_count' => 126], $page['summary']);
        self::assertSame(3, $page['total_count']);
        self::assertSame(['page_size' => 2, 'current_page' => 1, 'total_pages' => 2], $page['page_info']);
        self::assertSame(
            [
                'id' => 41,
                'nickname' => 'Sara',
                'title' => 'Great sofa',
                'text' => 'Comfortable and well made.',
                'rating' => 4.5,
                'created_at' => '2026-09-20T08:15:00Z',
                'product' => ['name' => 'Corner Sofa Bed', 'url_key' => 'corner-sofa-bed', 'thumbnail_url' => 'https://m/t.jpg'],
                'product_id' => 301,
            ],
            $page['items'][0]
        );
        //  Empty title and text are null, a review without votes has no rating, a product
        //  the collection did not return is null.
        self::assertNull($page['items'][1]['title']);
        self::assertNull($page['items'][1]['text']);
        self::assertNull($page['items'][1]['rating']);
        self::assertNull($page['items'][1]['product']);

        self::assertSame(
            ['hm_vendor_7', 'cat_p', 'cat_p_301', 'cat_p_302'],
            (new StoreReviewsIdentity())->getIdentities($page)
        );
    }

    public function testASellerWithoutReviewsStillHasItsSummary(): void
    {
        $reviews = $this->createMock(SellerReviews::class);
        $reviews->method('page')->willReturn(['total' => 0, 'rows' => []]);
        $reviews->expects(self::never())->method('ratings');
        $products = $this->createMock(ReviewProducts::class);
        $products->expects(self::never())->method('forPage');

        $page = $this->query($reviews, $products)->execute('MIA', 20, 1, 1);

        self::assertSame([], $page['items']);
        self::assertSame(0, $page['total_count']);
        self::assertSame(0, $page['page_info']['total_pages']);
        self::assertSame(['hm_vendor_7'], (new StoreReviewsIdentity())->getIdentities($page));
    }

    public function testACodeThatIsNotAnApprovedSellerHasNoPage(): void
    {
        $directory = $this->createMock(SellerDirectory::class);
        $directory->method('findApprovedByCode')->willReturn(null);
        $emulation = $this->createMock(StorefrontEmulationInterface::class);
        $emulation->expects(self::never())->method('run');

        $query = new StoreReviewsQuery(
            $directory,
            $this->createMock(SellerRatings::class),
            $this->createMock(SellerReviews::class),
            $this->createMock(ReviewProducts::class),
            $this->createMock(ListableProducts::class),
            $emulation
        );

        self::assertNull($query->execute('nobody', 20, 1, 1));
        self::assertSame([], (new StoreReviewsIdentity())->getIdentities([]));
    }

    private function query(SellerReviews $reviews, ReviewProducts $products): StoreReviewsQuery
    {
        $directory = $this->createMock(SellerDirectory::class);
        $directory->method('findApprovedByCode')->willReturnCallback(
            static fn (string $code): ?array => strtolower($code) === 'mia' ? self::MIA : null
        );
        $ratings = $this->createMock(SellerRatings::class);
        $ratings->method('forVendors')->with([7])->willReturn([7 => ['rating' => 4.8, 'review_count' => 126]]);
        $listable = $this->createMock(ListableProducts::class);
        $listable->method('forVendors')->willReturn([7 => [301, 305]]);
        $emulation = $this->createMock(StorefrontEmulationInterface::class);
        $emulation->expects(self::once())->method('run')
            ->willReturnCallback(static fn (int $storeId, callable $callback) => $callback());

        return new StoreReviewsQuery($directory, $ratings, $reviews, $products, $listable, $emulation);
    }
}
