<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Resolver;

use Magento\Framework\DataObject;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\GraphQlCache\Model\CacheableQuery;
use Magento\Store\Api\Data\StoreInterface;
use MagentoEgypt\HubAppVendors\Model\Offer\OfferFinder;
use MagentoEgypt\HubAppVendors\Model\Resolver\AbstractOfferResolver;
use MagentoEgypt\HubAppVendors\Model\Resolver\ProductOfferCount;
use MagentoEgypt\HubAppVendors\Model\Resolver\ProductOtherOffers;
use PHPUnit\Framework\TestCase;

/**
 * hm_offer_count and hm_other_offers: one finder call per response, the page
 * size rules of the app's lists, and the family's tags on the response.
 */
final class ProductOffersTest extends TestCase
{
    public function testTheCountAndTheListComeFromOneFinderCallForTheWholeResponse(): void
    {
        $offers = array_map(static fn (int $i): array => ['sku' => 'o' . $i], range(1, 12));
        $finder = $this->createMock(OfferFinder::class);
        $finder->expects(self::exactly(2))->method('offers')
            ->with([1, 2], 3)
            ->willReturn([1 => $offers, 2 => []]);
        $finder->method('cacheTags')->willReturn(['cat_p_1', 'cat_p_9', 'hm_vendor_4']);
        $cacheableQuery = new CacheableQuery();

        $count = (new ProductOfferCount($finder, $cacheableQuery))->resolve(
            $this->context(),
            $this->createMock(Field::class),
            [$first = $this->request(['entity_id' => 1]), $second = $this->request(['model' => new DataObject(['id' => 2])])]
        );
        self::assertSame(12, $count->findResponseFor($first));
        self::assertSame(0, $count->findResponseFor($second));

        $list = (new ProductOtherOffers($finder, $cacheableQuery))->resolve(
            $this->context(),
            $this->createMock(Field::class),
            [$a = $this->request(['entity_id' => 1], ['pageSize' => 10]), $b = $this->request(['entity_id' => 2], ['pageSize' => 10])]
        );
        self::assertSame(array_slice($offers, 0, 10), $list->findResponseFor($a), 'the default page is ten, cheapest first');
        self::assertSame([], $list->findResponseFor($b));

        self::assertSame(['cat_p_1', 'cat_p_9', 'hm_vendor_4'], $cacheableQuery->getCacheTags());
    }

    public function testThePageSizeIsCappedAtFiftyAndMustBePositive(): void
    {
        $offers = array_map(static fn (int $i): array => ['sku' => 'o' . $i], range(1, 60));
        $finder = $this->createMock(OfferFinder::class);
        $finder->method('offers')->willReturn([1 => $offers]);
        $finder->method('cacheTags')->willReturn([]);
        $resolver = new ProductOtherOffers($finder, new CacheableQuery());

        $response = $resolver->resolve($this->context(), $this->createMock(Field::class), [$item = $this->request(['entity_id' => 1], ['pageSize' => 500])]);
        self::assertCount(50, $response->findResponseFor($item));

        $response = $resolver->resolve($this->context(), $this->createMock(Field::class), [$item = $this->request(['entity_id' => 1], ['pageSize' => 2])]);
        self::assertSame(['o1', 'o2'], array_column($response->findResponseFor($item), 'sku'));

        $this->expectException(GraphQlInputException::class);
        $resolver->resolve($this->context(), $this->createMock(Field::class), [$this->request(['entity_id' => 1], ['pageSize' => 0])]);
    }

    public function testAValueWithoutAProductAsksNothing(): void
    {
        $finder = $this->createMock(OfferFinder::class);
        $finder->expects(self::never())->method('offers');
        $resolver = new ProductOfferCount($finder, new CacheableQuery());

        $response = $resolver->resolve($this->context(), $this->createMock(Field::class), [$item = $this->request([])]);

        self::assertSame(0, $response->findResponseFor($item));
        self::assertSame(0, AbstractOfferResolver::productId(null));
        self::assertSame(5, AbstractOfferResolver::productId(['entity_id' => '5']));
    }

    /**
     * @param array<string, mixed> $value
     * @param array<string, mixed>|null $args
     */
    private function request(array $value, ?array $args = null): BatchRequestItemInterface
    {
        $request = $this->createMock(BatchRequestItemInterface::class);
        $request->method('getValue')->willReturn($value);
        $request->method('getArgs')->willReturn($args);

        return $request;
    }

    private function context(): ContextInterface
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(3);
        $extension = new class ($store) {
            public function __construct(private readonly StoreInterface $store)
            {
            }

            public function getStore(): StoreInterface
            {
                return $this->store;
            }
        };
        $context = $this->getMockBuilder(ContextInterface::class)->addMethods(['getExtensionAttributes'])->getMock();
        $context->method('getExtensionAttributes')->willReturn($extension);

        return $context;
    }
}
