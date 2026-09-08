<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SearchLanding\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Search\Model\QueryFactory;
use MagentoEgypt\SearchLanding\Model\SearchFacets;
use Psr\Log\LoggerInterface;

/**
 * The Figma search chrome, on Magento's real results page.
 *
 * /search is a PREVIEW — twelve products, no pagination. The page a submitted
 * search actually lands on is catalogsearch/result, which has the sorting,
 * paging and layered navigation a real catalogue needs and none of the design's
 * chrome. This view model supplies that chrome so the two stop looking like
 * different products.
 *
 * THE PRODUCT COUNT IS NOT COMPUTED HERE — see getProductCount(). Every
 * server-side way of getting it either disagrees with the grid or changes it:
 * a search of our own returns a different set (that is how /search came to claim
 * 12 products for "bag" while the page listed 10), and asking the layer from
 * this block — which renders before the grid — executes the search early and
 * alters the results outright. The toolbar is the only thing on the page that
 * has genuinely counted, so it publishes the number and the JS reads it.
 *
 * Vendors and categories ARE counted here: they are independent of the product
 * search and nothing else on the page produces them.
 */
class ResultsHeader implements ArgumentInterface
{
    public function __construct(
        private readonly QueryFactory $queryFactory,
        private readonly SearchFacets $facets,
        private readonly UrlInterface $url,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getQueryText(): string
    {
        try {
            return trim((string) $this->queryFactory->get()->getQueryText());
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Where the hero field posts — the same page, so a new query replaces this one. */
    public function getSearchActionUrl(): string
    {
        return $this->url->getUrl('catalogsearch/result');
    }

    /**
     * DELIBERATELY NOT ANSWERED HERE — the toolbar owns this number.
     *
     * The first version asked the search layer: `layerResolver->get()
     * ->getProductCollection()->getSize()`. That is the collection the grid
     * renders, so it looked like the safest possible source. It is not. This
     * block sits in `columns.top`, which renders BEFORE `.columns`, so asking for
     * a size there executes the search before the layer has finished applying its
     * filters — and the frozen response is then what the grid shows. Measured:
     * "bag" went from 10 results to 21 on the page itself, with this header
     * claiming 24. Reading a count changed the results.
     *
     * So the count is filled in by web/js/results-tabs.js from the
     * `data-total` the toolbar prints once it has genuinely counted. See
     * Magento_Catalog::product/list/toolbar/amount.phtml.
     */
    public function getProductCount(): ?int
    {
        return null;
    }

    /**
     * @return array<int, array{id:int,name:string,url:string,city:?string}>
     */
    public function getVendors(): array
    {
        return $this->facets->getVendors($this->getQueryText());
    }

    /**
     * @return array<int, array{id:int,name:string,url:string,count:int}>
     */
    public function getCategories(): array
    {
        return $this->facets->getCategories($this->getQueryText());
    }

    /**
     * Vendors + categories only. The product term is added in JS once the
     * toolbar has published its count — see getProductCount().
     */
    public function getFacetTotal(): int
    {
        return count($this->getVendors()) + count($this->getCategories());
    }
}
