<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Config;

use Magento\Store\Model\Store;
use MagentoEgypt\HubApp\Model\Config\AlgoliaLayout;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * hmAppConfig.algolia's search layout, as window.algoliaConfig carries it.
 *
 * @covers \MagentoEgypt\HubApp\Model\Config\AlgoliaLayout
 */
class AlgoliaLayoutTest extends TestCase
{
    public function testFacetsInAdminOrderWithOtherTypesResolved(): void
    {
        self::assertSame(
            [
                ['attribute' => 'price', 'type' => 'slider', 'label' => 'السعر'],
                ['attribute' => 'mgs_brand', 'type' => 'disjunctive', 'label' => 'العلامة التجارية'],
                ['attribute' => 'rating_summary', 'type' => 'priceRanges', 'label' => ''],
            ],
            AlgoliaLayout::facets([
                ['attribute' => 'price', 'type' => 'slider', 'label' => 'السعر', 'searchable' => '2'],
                ['attribute' => 'mgs_brand', 'type' => 'disjunctive', 'label' => 'العلامة التجارية '],
                ['attribute' => '', 'type' => 'disjunctive', 'label' => 'nothing'],
                ['attribute' => 'rating_summary', 'type' => 'other', 'other_type' => 'priceRanges'],
                'junk',
            ])
        );
    }

    public function testSortsAreTheGuestReplicas(): void
    {
        self::assertSame(
            [
                ['index' => 'hubmarket_en_products_price_default_asc', 'attribute' => 'price', 'direction' => 'ASC', 'label' => 'Lowest price'],
                ['index' => 'hubmarket_en_products_created_at_desc', 'attribute' => 'created_at', 'direction' => 'DESC', 'label' => 'Newest first'],
            ],
            AlgoliaLayout::sorts([
                ['attribute' => 'price', 'sort' => 'asc', 'sortLabel' => 'Lowest price', 'name' => 'hubmarket_en_products_price_default_asc', 'label' => 'Lowest price'],
                ['attribute' => 'created_at', 'sort' => 'desc', 'sortLabel' => 'Newest first', 'name' => 'hubmarket_en_products_created_at_desc'],
                ['attribute' => 'name', 'sort' => 'sideways', 'name' => 'hubmarket_en_products_name_sideways'],
                ['attribute' => 'price', 'sort' => 'asc'],
            ])
        );
    }

    public function testSuggestionsFollowTheStorefrontsAutocomplete(): void
    {
        //  Off on the live store (29 Sep 2026): no index, the app shows no "Try" chips.
        self::assertSame(['suggestion_index' => null, 'suggestion_count' => 0], AlgoliaLayout::suggestions(0, '', 2, 0, 'hubmarket_en'));
        //  Magento search terms: the extension's own <index name>_suggestions.
        self::assertSame(
            ['suggestion_index' => 'hubmarket_en_suggestions', 'suggestion_count' => 5],
            AlgoliaLayout::suggestions(1, 'ignored', 2, 5, 'hubmarket_en')
        );
        self::assertSame(['suggestion_index' => null, 'suggestion_count' => 0], AlgoliaLayout::suggestions(1, '', 2, 0, 'hubmarket_en'));
        //  Algolia Query Suggestions: the configured index.
        self::assertSame(
            ['suggestion_index' => 'hubmarket_en_products_query_suggestions', 'suggestion_count' => 3],
            AlgoliaLayout::suggestions(2, ' hubmarket_en_products_query_suggestions ', 3, 5, 'hubmarket_en')
        );
        self::assertSame(['suggestion_index' => null, 'suggestion_count' => 0], AlgoliaLayout::suggestions(2, '', 3, 5, 'hubmarket_en'));
    }

    public function testPageSuggestionsComeFromThePagesSection(): void
    {
        self::assertSame(2, AlgoliaLayout::pageSuggestions([['name' => 'pages', 'label' => 'Pages', 'hitsPerPage' => '2']]));
        self::assertSame(0, AlgoliaLayout::pageSuggestions([['name' => 'mgs_brand', 'hitsPerPage' => '4']]));
        self::assertSame(0, AlgoliaLayout::pageSuggestions([]));
    }

    public function testTheLayoutOfAStoreViewIsReadOncePerRequest(): void
    {
        foreach ([
            'Algolia\AlgoliaSearch\Helper\Configuration\InstantSearchHelper',
            'Algolia\AlgoliaSearch\Helper\Configuration\AutocompleteHelper',
            'Algolia\AlgoliaSearch\Service\Product\SortingTransformer',
            'Algolia\AlgoliaSearch\Helper\ConfigHelper',
        ] as $class) {
            if (!class_exists($class)) {
                self::markTestSkipped($class . ' is not in this test runtime (the Algolia extension is older or absent).');
            }
        }
        $instant = $this->algoliaMock(
            'Algolia\AlgoliaSearch\Helper\Configuration\InstantSearchHelper',
            ['getFacets', 'getMaxValuesPerFacet']
        );
        $instant->expects(self::once())->method('getFacets')->with(1)
            ->willReturn([['attribute' => 'price', 'type' => 'slider', 'label' => 'Price']]);
        $instant->method('getMaxValuesPerFacet')->willReturn(10);

        $autocomplete = $this->algoliaMock('Algolia\AlgoliaSearch\Helper\Configuration\AutocompleteHelper', [
            'getSuggestionsMode', 'getSuggestionsIndexName', 'getNumberOfAlgoliaSuggestions',
            'getNumberOfQueriesSuggestions', 'getNumberOfProductsSuggestions',
            'getNumberOfCategoriesSuggestions', 'getAdditionalSections',
        ]);
        $autocomplete->method('getSuggestionsMode')->willReturn(0);
        $autocomplete->method('getSuggestionsIndexName')->willReturn('');
        $autocomplete->method('getNumberOfAlgoliaSuggestions')->willReturn(2);
        $autocomplete->method('getNumberOfQueriesSuggestions')->willReturn(0);
        $autocomplete->method('getNumberOfProductsSuggestions')->willReturn(8);
        $autocomplete->method('getNumberOfCategoriesSuggestions')->willReturn(2);
        $autocomplete->method('getAdditionalSections')->willReturn([['name' => 'pages', 'hitsPerPage' => '2']]);

        $sorting = $this->algoliaMock('Algolia\AlgoliaSearch\Service\Product\SortingTransformer', ['getSortingIndices']);
        $sorting->expects(self::once())->method('getSortingIndices')->with(1, AlgoliaLayout::GUEST_GROUP)->willReturn([
            ['attribute' => 'price', 'sort' => 'asc', 'name' => 'hubmarket_en_products_price_default_asc', 'label' => 'Lowest price'],
        ]);

        $config = $this->algoliaMock(
            'Algolia\AlgoliaSearch\Helper\ConfigHelper',
            ['isCustomerGroupsEnabled', 'getCategorySeparator', 'showCatsNotIncludedInNavigation']
        );
        $config->method('isCustomerGroupsEnabled')->willReturn(false);
        $config->method('getCategorySeparator')->willReturn(' /// ');
        $config->method('showCatsNotIncludedInNavigation')->willReturn(false);

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getCurrentCurrencyCode'])
            ->getMock();
        $store->method('getId')->willReturn(1);
        $store->method('getCurrentCurrencyCode')->willReturn('AED');

        $layout = new AlgoliaLayout($instant, $autocomplete, $sorting, $config, $this->createMock(LoggerInterface::class));
        $expected = [
            'facets' => [['attribute' => 'price', 'type' => 'slider', 'label' => 'Price']],
            'sorts' => [['index' => 'hubmarket_en_products_price_default_asc', 'attribute' => 'price', 'direction' => 'ASC', 'label' => 'Lowest price']],
            'suggestion_index' => null,
            'suggestion_count' => 0,
            'currency_code' => 'AED',
            'price_group' => 'default',
            'max_values_per_facet' => 10,
            'product_suggestions' => 8,
            'category_suggestions' => 2,
            'page_suggestions' => 2,
            'category_separator' => ' /// ',
            'categories_outside_menu' => false,
        ];

        self::assertSame($expected, $layout->forStore($store, 'hubmarket_en'));
        self::assertSame($expected, $layout->forStore($store, 'hubmarket_en'), 'remembered');
    }

    /**
     * A mock of an Algolia extension class, whose methods differ between the
     * extension's versions: the ones this version lacks are declared by the mock.
     *
     * @param string[] $methods
     */
    private function algoliaMock(string $class, array $methods): MockObject
    {
        $builder = $this->getMockBuilder($class)->disableOriginalConstructor();
        $known = array_values(array_filter($methods, static fn (string $m): bool => method_exists($class, $m)));
        $missing = array_values(array_diff($methods, $known));
        $builder->onlyMethods($known);
        if ($missing) {
            $builder->addMethods($missing);
        }

        return $builder->getMock();
    }
}
