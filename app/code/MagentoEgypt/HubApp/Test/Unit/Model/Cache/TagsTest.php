<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Cache;

use MagentoEgypt\HubApp\Model\Cache\ResponseTtl;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Resolver\Paging;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use PHPUnit\Framework\TestCase;

/**
 * Cache tags, the HTTP cache lifetime cap and list paging.
 */
final class TagsTest extends TestCase
{
    public function testProductTagsCarryTheGenericTagFirst(): void
    {
        self::assertSame(['cat_p', 'cat_p_3', 'cat_p_5'], Tags::products([3, 0, 3, 5]));
        self::assertSame([], Tags::products([]));
    }

    public function testAppCacheNeverCarriesProductTags(): void
    {
        self::assertSame(
            ['hm_app_home', 'cat_c_p_2', 'cms_b'],
            Tags::forAppCache(['hm_app_home', 'cat_p', 'cat_p_1', 'cat_c_p_2', 'cms_b', ''])
        );
    }

    public function testCmsBlockTags(): void
    {
        self::assertSame(['cms_b_4', 'cms_b_hm_home_trust'], Tags::cmsBlock(4, 'hm_home_trust'));
    }

    public function testResponseTtlKeepsTheLowestCap(): void
    {
        $ttl = new ResponseTtl();
        self::assertSame(86400, $ttl->apply(86400));

        $ttl->cap(3600);
        $ttl->cap(7200);
        self::assertSame(3600, $ttl->apply(86400));
        self::assertSame(60, $ttl->apply(60));

        $ttl->_resetState();
        self::assertNull($ttl->getCap());
    }

    public function testPaging(): void
    {
        self::assertSame([50, 2], Paging::args(['pageSize' => 500, 'currentPage' => 2], 20, 50));
        self::assertSame([20, 1], Paging::args([], 20, 50));
        self::assertSame(['page_size' => 20, 'current_page' => 3, 'total_pages' => 3], Paging::info(41, 20, 3));
        self::assertSame([5], Paging::slice([1, 2, 3, 4, 5], 2, 3));
        self::assertSame([], Paging::slice([1, 2], 2, 5));
    }

    public function testPagingRefusesPagesBelowOne(): void
    {
        $this->expectException(GraphQlInputException::class);
        Paging::args(['currentPage' => 0], 20, 50);
    }
}
