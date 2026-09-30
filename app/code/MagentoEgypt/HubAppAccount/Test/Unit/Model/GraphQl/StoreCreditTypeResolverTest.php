<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\GraphQl;

use Magento\Catalog\Model\Product\Type\Virtual;
use MagentoEgypt\HubAppAccount\Model\GraphQl\StoreCreditTypeResolver;
use PHPUnit\Framework\TestCase;
use Vnecoms\Credit\Model\Product\Type\Credit;

/**
 * Vnecoms' store_credit product answers as a virtual product wherever GraphQL resolves a product type.
 */
final class StoreCreditTypeResolverTest extends TestCase
{
    public function testStoreCreditIsAVirtualProductAndNothingElseIsClaimed(): void
    {
        $resolver = new StoreCreditTypeResolver();

        self::assertSame('VirtualProduct', $resolver->resolveType(['type_id' => 'store_credit']));
        //  Every other type is left to the resolvers registered for it.
        foreach (['simple', 'virtual', 'bundle', 'new_bundle', 'configurable', ''] as $type) {
            self::assertSame('', $resolver->resolveType(['type_id' => $type]), $type);
        }
        self::assertSame('', $resolver->resolveType([]));
    }

    public function testTheTypeModelIsMagentosVirtualType(): void
    {
        self::assertSame(StoreCreditTypeResolver::TYPE_ID, Credit::TYPE_CODE);
        self::assertTrue(is_subclass_of(Credit::class, Virtual::class));
    }

    public function testTheCartTheWishlistAndTheProductResolversAreWired(): void
    {
        $etc = dirname(__DIR__, 4) . '/etc';
        $global = $this->xpath($etc . '/di.xml');
        $graphql = $this->xpath($etc . '/graphql/di.xml');

        self::assertSame('VirtualCartItem', $this->item(
            $global,
            'Magento\QuoteGraphQl\Model\Resolver\CartItemTypeResolver',
            'supportedTypes',
            'store_credit'
        ));
        self::assertSame('VirtualWishlistItem', $this->item(
            $graphql,
            'Magento\WishlistGraphQl\Model\Resolver\Type\WishlistItemType',
            'supportedTypes',
            'store_credit'
        ));
        foreach ([
            'Magento\CatalogGraphQl\Model\ProductInterfaceTypeResolverComposite',
            'Magento\UrlRewriteGraphQl\Model\RoutableInterfaceTypeResolver',
        ] as $composite) {
            self::assertSame(StoreCreditTypeResolver::class, $this->item(
                $graphql,
                $composite,
                'productTypeNameResolvers',
                'magentoegypt_store_credit_type_resolver'
            ), $composite);
        }
    }

    private function xpath(string $file): \DOMXPath
    {
        $dom = new \DOMDocument();
        self::assertTrue($dom->load($file), $file);

        return new \DOMXPath($dom);
    }

    private function item(\DOMXPath $xpath, string $type, string $argument, string $item): ?string
    {
        $nodes = $xpath->query(sprintf(
            '/config/type[@name="%s"]/arguments/argument[@name="%s"]/item[@name="%s"]',
            $type,
            $argument,
            $item
        ));

        return $nodes->length === 1 ? trim($nodes->item(0)->textContent) : null;
    }
}
