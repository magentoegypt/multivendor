<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SearchLanding\Block\Ajax;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use MagentoEgypt\SearchLanding\Model\SearchFacets;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Review\Model\Review;
use Magento\Review\Model\ResourceModel\Review\SummaryFactory as ReviewSummaryFactory;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HomeSections\ViewModel\VendorNames;
use Psr\Log\LoggerInterface;

/**
 * Live-search results — the three tab panels behind the /search hero field.
 *
 * EXTENDS AbstractProduct ON PURPOSE. The product panel reuses the storefront's
 * own card markup (product-item / hm-card__*), and that markup calls
 * getImage(), getProductUrl(), getReviewsSummaryHtml() and getProductPriceHtml()
 * — all of which live on AbstractProduct. Rendering cards from a plain Template
 * block would have meant reimplementing price and rating output in the template
 * or, worse, in JavaScript, and the two would drift from the PLP the first time
 * anyone touched pricing.
 *
 * EVERY SOURCE IS CAPPED and every lookup is wrapped. This block runs on
 * keystrokes, and the www FPM pool on this host allows 5 children across 2
 * cores, so an uncapped or throwing query here is a site-wide outage rather than
 * a broken panel. Each panel degrades to empty independently.
 */
class Results extends AbstractProduct
{
    public const PRODUCT_LIMIT = 12;
    public const VENDOR_LIMIT = 6;
    public const CATEGORY_LIMIT = 6;

    /** Matches Magento's own minimum query length; below it, everything matches. */
    public const MIN_QUERY_LENGTH = 3;

    private ?array $vendors = null;
    private ?array $categories = null;
    private $products = null;

    /**
     * NOTE the type-hint on $fulltextCollectionFactory.
     *
     * `Magento\CatalogSearch\Model\ResourceModel\Fulltext\CollectionFactory` is a
     * VIRTUAL TYPE (module-catalog-search/etc/di.xml:98), not a class with a file
     * behind it. Type-hinting it resolves fine at runtime and then fails
     * setup:di:compile with "class does not exist", because there is no source
     * class for the compiler to generate a factory from — a failure that only
     * shows up in production mode, i.e. on deploy rather than in development.
     *
     * So the hint is the REAL base factory and etc/di.xml passes the virtual type
     * in. What create() returns is still the fulltext collection.
     */
    public function __construct(
        Context $context,
        private readonly ProductCollectionFactory $fulltextCollectionFactory,
        private readonly Visibility $catalogVisibility,
        private readonly CatalogConfig $catalogConfig,
        private readonly SearchFacets $facets,
        private readonly StoreManagerInterface $storeManager,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly ReviewSummaryFactory $reviewSummaryFactory,
        private readonly VendorNames $vendorNames,
        private readonly LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Format an amount in the store's currency.
     *
     * THE PRICE IS RENDERED BY HAND HERE, not by getProductPriceHtml(), and that
     * is a deliberate trade rather than an oversight.
     *
     * getProductPriceHtml() delegates to the `product.price.render.default`
     * block, which only exists once a full page layout has been built. This
     * controller builds a bare layout on purpose — constructing the whole default
     * layout on every keystroke, on a host with five FPM children, is exactly the
     * cost this endpoint is designed to avoid. Called from a bare layout the
     * helper does not fail; it silently returns an empty string, which is how the
     * first version shipped twelve cards with an empty price row.
     *
     * What this loses is the renderer's extras: tier pricing, "As low as" for
     * configurables, and tax-display variants. For a preview panel showing final
     * and was-price that is the whole of what the design asks for, and the "See
     * all products" link goes to the real results page, which renders the
     * complete price box.
     */
    public function formatPrice(float $amount): string
    {
        return (string) $this->priceCurrency->format($amount, false);
    }

    /**
     * Regular and final price for a product, plus the discount between them.
     *
     * Read from the price MODEL rather than the special_price attribute, so
     * catalog price rules and tier prices are included and the badge can never
     * disagree with the number beside it.
     *
     * @return array{regular:float,final:float,discount:int}
     */
    public function getPricing(\Magento\Catalog\Model\Product $product): array
    {
        try {
            $priceInfo = $product->getPriceInfo();
            $regular = (float) $priceInfo
                ->getPrice(\Magento\Catalog\Pricing\Price\RegularPrice::PRICE_CODE)
                ->getAmount()->getValue();
            $final = (float) $priceInfo
                ->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE)
                ->getAmount()->getValue();
        } catch (\Throwable $e) {
            return ['regular' => 0.0, 'final' => 0.0, 'discount' => 0];
        }

        $discount = 0;
        if ($regular > 0 && $final > 0 && $final < $regular) {
            $discount = (int) round((1 - $final / $regular) * 100);
        }

        return ['regular' => $regular, 'final' => $final, 'discount' => $discount];
    }

    public function getQueryText(): string
    {
        return trim((string) $this->getData('query_text'));
    }

    /**
     * Products matching the query, through the catalog search index — the same
     * OpenSearch path /catalogsearch/result uses, so the live panel and the full
     * results page can never disagree about what matches.
     *
     * @return \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection|null
     */
    public function getProducts()
    {
        if ($this->products !== null) {
            return $this->products;
        }

        try {
            $collection = $this->fulltextCollectionFactory->create();
            /*
             * catalogConfig->getProductAttributes() — the SAME attribute set the
             * PLP selects, not a hand-picked list.
             *
             * The hand-picked version (name + images + vendor_id) rendered every
             * card at its full price with no discount badge, because special_price
             * was not among the loaded attributes and getPriceInfo() therefore
             * computed a final price equal to the regular one. Savvy Shoulder Tote
             * is 32 marked down to 24 and showed as 32. Nothing errored — the
             * price was just quietly wrong, which is the worst way for a price to
             * be wrong.
             */
            $collection->addAttributeToSelect($this->catalogConfig->getProductAttributes())
                ->addAttributeToSelect(['small_image', 'thumbnail', 'image', 'vendor_id'])
                ->addSearchFilter($this->getQueryText())
                ->setVisibility($this->catalogVisibility->getVisibleInSearchIds())
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->addUrlRewrite()
                ->setPageSize(self::PRODUCT_LIMIT)
                ->setCurPage(1);

            /*
             * Ratings, appended BEFORE the load below — appendSummaryFieldsToCollection
             * only acts `if (!$productCollection->isLoaded())` and silently does
             * nothing afterwards, so the order of these two statements is the
             * difference between stars and no stars.
             *
             * This is the collection-level equivalent of what the review module's
             * CatalogProductListCollectionAppendSummaryFieldsObserver does for the
             * PLP. getReviewsSummaryHtml() is unavailable here for the same reason
             * getProductPriceHtml() was: it needs a renderer from a full page
             * layout, and this endpoint builds a bare one on purpose.
             */
            $this->reviewSummaryFactory->create()->appendSummaryFieldsToCollection(
                $collection,
                (int) $this->storeManager->getStore()->getId(),
                Review::ENTITY_PRODUCT_CODE
            );

            // Force the query now so a search-engine failure is caught HERE and
            // degrades to an empty panel, rather than throwing mid-template and
            // returning half-rendered HTML to the browser.
            $collection->load();

            return $this->products = $collection;
        } catch (\Throwable $e) {
            $this->logger->warning('SearchLanding live products: ' . $e->getMessage());
            return $this->products = null;
        }
    }

    public function getProductCount(): int
    {
        $collection = $this->getProducts();

        try {
            return $collection ? (int) $collection->getSize() : 0;
        } catch (\Throwable $e) {
            return $collection ? count($collection) : 0;
        }
    }

    /**
     * Vendors whose company name matches.
     *
     * Read straight off ves_vendor_entity rather than through the Vnecoms vendor
     * collection: that collection loads the full EAV row set per vendor, and this
     * runs on keystrokes. There are 25 vendor rows on this install, so a LIKE
     * against a plain column is the cheapest correct answer.
     *
     * @return array<int, array{id:int,name:string,url:string,city:?string}>
     */
    public function getVendors(): array
    {
        return $this->facets->getVendors($this->getQueryText(), self::VENDOR_LIMIT);
    }

    /**
     * Categories whose name matches, restricted to ones a shopper can actually
     * reach — active and in the menu.
     *
     * @return array<int, array{id:int,name:string,url:string,count:int}>
     */
    public function getCategories(): array
    {
        return $this->facets->getCategories($this->getQueryText(), self::CATEGORY_LIMIT);
    }

    /**
     * The selling vendor's name, or null.
     *
     * On a marketplace this is the one line that separates two otherwise
     * identical listings, and the reference prints it above every card title.
     * The PLP and the homepage rails already show it; leaving it off the search
     * preview made the same product look like a different product there.
     *
     * Same batch map those pages use — one query for all 25 vendors on the
     * install, not a lookup per card.
     */
    public function getVendorName(\Magento\Catalog\Model\Product $product): ?string
    {
        try {
            return $this->vendorNames->getName($product->getData('vendor_id'));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Where "see all results" goes — the real, paginated, filterable page. */
    public function getFullResultsUrl(): string
    {
        return $this->getUrl('catalogsearch/result', ['_query' => ['q' => $this->getQueryText()]]);
    }
}
