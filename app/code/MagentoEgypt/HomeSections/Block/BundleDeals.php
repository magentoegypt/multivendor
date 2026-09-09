<?php
/**
 * Hub Market — Bundle Deals rail.
 *
 * The Figma reference draws four rich bundle cards on the navy band: image with a
 * "-19% Bundle" badge and a "Save $200" pill, an item-count chip, the seller, a
 * serif title, a description, a strip of the included products' thumbnails, a
 * rating, price beside a struck-through total, a green "You save $199.98" line
 * and an "Add Bundle" button. Ours rendered the band and a "Browse bundles"
 * button, and no cards at all.
 *
 * WHAT IS REAL HERE AND WHAT IS NOT
 * ---------------------------------
 * Every field on the card is read from the catalog. Two of the reference's are
 * therefore conditional rather than decorative, and on this catalog today they do
 * not render:
 *
 *   1. THE DISCOUNT. Measured across all three enabled bundles, every selection
 *      carries selection_price_type = 0 and selection_price_value = 0 — no bundle
 *      discount is configured anywhere. A dynamic-price bundle with no selection
 *      discount costs exactly the sum of its parts, so there is nothing to save
 *      and "-19% Bundle" would be an invented claim about a price. The badge, the
 *      "Save X" pill and the "You save X" line are all gated on a saving that is
 *      actually greater than zero, and they light up on their own the moment a
 *      merchandiser configures one (or a child product goes on special, which
 *      this does pick up — see getRegularTotal()).
 *
 *   2. THE ITEM COUNT. The reference says "4 items". The truthful count is the
 *      number of REQUIRED OPTIONS, not the number of selection rows: an option is
 *      one product the customer ends up with. Bundle 45 has four required radio
 *      options and genuinely is a four-item kit. "Gaming Set" and "house tools"
 *      each have ONE option holding six or seven selections — the customer picks
 *      one of them, so "7 items" would be wrong by a factor of seven. Those get
 *      "Choose from 7" instead, which is both true and more useful.
 *
 * Both `bundle` and `new_bundle` are collected. new_bundle is this project's own
 * product type (MagentoEgypt_BundleExtend) and stores its options and selections
 * in the core bundle tables, so one query serves both.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HomeSections\ViewModel\ReviewStars;
use MagentoEgypt\HomeSections\ViewModel\VendorNames;
use Psr\Log\LoggerInterface;

class BundleDeals extends Template
{
    /** Product types whose options live in the core bundle tables. */
    private const TYPES = ['bundle', 'new_bundle'];

    /** Thumbnails shown before the strip collapses into a "+N" chip. */
    private const THUMB_LIMIT = 4;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $bundles = null;

    /**
     * Star rating for a bundle card.
     *
     * This block extends Template rather than AbstractProduct — it builds its own
     * rows from a collection — so it does not inherit AbstractProduct's version of
     * this method. Delegating to the same ReviewRendererInterface keeps the markup
     * identical to every other card on the storefront, which matters because the
     * theme's summary_short.phtml override is what supplies the "(N)" format and
     * the empty-state track.
     *
     * THE RENDERER ARRIVES AS A LAYOUT ARGUMENT, NOT THROUGH THE CONSTRUCTOR.
     * Adding a constructor parameter to a block would need `setup:di:compile` to
     * take effect in production, and that wipes `generated/` — a multi-minute 500
     * on a live storefront for one star rating. The layout-argument route is also
     * already this section's convention: `vendor_names` reaches four rails the
     * same way. It degrades to no rating rather than to a fatal if the argument
     * is ever dropped.
     */
    public function getReviewsSummaryHtml(Product $product): string
    {
        $stars = $this->getData('review_stars');

        return $stars instanceof ReviewStars ? $stars->forProduct($product) : '';
    }

    /**
     * Rating figures for the card, as DATA — not rendered HTML.
     *
     * The bundle card is one <a>. The shared summary template renders its count
     * as an <a class="action view">, and an anchor inside an anchor is invalid
     * HTML: the parser closes the card at the inner link, which shattered every
     * bundle into three sibling fragments (measured 504px + 30px + 2px) and
     * spilled the prices and CTA outside the card — the "Bundle section UI
     * broken" report. The template now renders spans from these figures instead;
     * getReviewsSummaryHtml() above stays for any non-anchor context.
     *
     * One query for the whole rail, default-scope summary only — the store-2
     * rows are all zero and dilute the average (same trap VendorMeta hit).
     *
     * @param int[] $productIds
     * @return array<int, array{pct: int, count: int}>
     */
    public function getRatingFigures(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }
        try {
            $conn = $this->resource->getConnection();
            $rows = $conn->fetchAll(
                $conn->select()
                    ->from(['s' => $this->resource->getTableName('review_entity_summary')],
                        ['entity_pk_value', 'rating_summary', 'reviews_count'])
                    ->where('s.entity_type = ?', 1)
                    ->where('s.store_id = ?', 0)
                    ->where('s.entity_pk_value IN (?)', $productIds)
            );
            $out = [];
            foreach ($rows as $row) {
                $out[(int) $row['entity_pk_value']] = [
                    'pct'   => (int) $row['rating_summary'],
                    'count' => (int) $row['reviews_count'],
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            $this->logger->warning('Hub Market bundle ratings: ' . $e->getMessage());
            return [];
        }
    }

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly ResourceConnection $resource,
        private readonly ImageHelper $imageHelper,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly StoreManagerInterface $storeManager,
        private readonly Visibility $visibility,
        private readonly VendorNames $vendorNames,
        private readonly LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBundles(): array
    {
        if ($this->bundles !== null) {
            return $this->bundles;
        }

        $this->bundles = [];

        try {
            $this->bundles = $this->build();
        } catch (\Throwable $e) {
            /*
             * A rail is not worth the homepage. Logged rather than swallowed so a
             * broken bundle index is still visible.
             */
            $this->logger->warning('HomeSections: bundle rail unavailable: ' . $e->getMessage());
            $this->bundles = [];
        }

        return $this->bundles;
    }

    public function getMoreUrl(): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/bundles';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function build(): array
    {
        $limit = (int) ($this->getData('limit') ?: 4);

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect(['name', 'small_image', 'thumbnail', 'image', 'description'])
            ->addAttributeToFilter('type_id', ['in' => self::TYPES])
            ->addAttributeToFilter('status', 1)
            /*
             * INSTANCE call. Visibility::getVisibleInCatalogIds() is not static in
             * 2.4.x — calling it statically throws, and because the whole build is
             * wrapped in a catch the rail just silently rendered nothing.
             */
            ->setVisibility($this->visibility->getVisibleInCatalogIds())
            ->addStoreFilter($this->storeManager->getStore())
            ->addAttributeToSort('entity_id', 'desc');
        $collection->setPageSize($limit * 2);   // room to drop any that price out

        if (!$collection->getSize()) {
            return [];
        }

        $ids       = array_map('intval', $collection->getAllIds());
        $options   = $this->loadOptions($ids);
        $indexed   = $this->loadIndexPrices($ids);
        $childData = $this->loadChildren($options);

        $out = [];
        foreach ($collection as $product) {
            $id = (int) $product->getId();

            $opts = $options[$id] ?? [];
            if (!$opts) {
                continue;   // a bundle with no options cannot be bought
            }

            $price = $indexed[$id] ?? null;
            if ($price === null || $price['min'] <= 0) {
                continue;   // not price-indexed yet; showing it would print "EGP 0"
            }

            $required = array_values(array_filter($opts, static fn (array $o): bool => (bool) $o['required']));
            $counted  = $required ?: $opts;

            /*
             * One option = the customer picks ONE of its selections, so the card
             * says how many there are to choose from. More than one required
             * option = one product per option, which is a kit.
             */
            $isKit      = count($counted) > 1;
            $choiceSize = (int) ($counted[0]['selection_count'] ?? 0);

            $regular = $this->getRegularTotal($counted, $childData, $isKit);
            $saving  = $regular > 0 ? $regular - $price['min'] : 0.0;
            if ($saving < 0.005) {
                $saving  = 0.0;
                $regular = 0.0;
            }

            $out[] = [
                'id'          => $id,
                'name'        => (string) $product->getName(),
                'url'         => $product->getProductUrl(),
                'image'       => $this->bannerUrl($product),
                'vendor'      => $this->vendorNames->getName($product->getData('vendor_id')),
                'description' => $this->excerpt((string) $product->getData('description'))
                    ?? $this->describeContents($counted, $childData),
                'is_kit'      => $isKit,
                'item_count'  => $isKit ? count($counted) : $choiceSize,
                'thumbs'      => $this->thumbsFor($counted, $childData),
                'extra'       => max(0, $this->thumbTotal($counted, $isKit) - self::THUMB_LIMIT),
                'price'       => $this->priceCurrency->format($price['min'], false),
                'price_from'  => $price['max'] > $price['min'] + 0.005,
                'regular'     => $regular > 0 ? $this->priceCurrency->format($regular, false) : null,
                'saving'      => $saving > 0 ? $this->priceCurrency->format($saving, false) : null,
                'discount'    => $saving > 0 && $regular > 0 ? (int) round($saving / $regular * 100) : 0,
                /*
                 * The product itself, for the rating block. Carried rather than
                 * pre-rendered because the renderer needs a template and this
                 * method runs inside a cached data build — rendering here would
                 * bake one store's stars into every store's cached row.
                 */
                'product'     => $product,
            ];

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * Options per bundle, each with its selection count and cheapest selection.
     *
     * ORDER BY on the joined selection price is what makes "cheapest" meaningful:
     * a dynamic bundle's indexed min_price is the cheapest choice in every option,
     * so the comparison total has to be built from the same selections or the
     * "you save" figure compares two different baskets.
     *
     * @param array<int, int> $parentIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadOptions(array $parentIds): array
    {
        if (!$parentIds) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $select     = $connection->select()
            ->from(
                ['o' => $this->resource->getTableName('catalog_product_bundle_option')],
                ['option_id', 'parent_id', 'required']
            )
            ->joinLeft(
                ['s' => $this->resource->getTableName('catalog_product_bundle_selection')],
                's.option_id = o.option_id',
                [
                    'selection_count' => new \Zend_Db_Expr('COUNT(s.selection_id)'),
                    'product_ids'     => new \Zend_Db_Expr('GROUP_CONCAT(s.product_id ORDER BY s.position, s.selection_id)'),
                    'qty'             => new \Zend_Db_Expr('MIN(s.selection_qty)'),
                ]
            )
            ->where('o.parent_id IN (?)', $parentIds)
            ->group('o.option_id')
            ->order(['o.parent_id', 'o.position', 'o.option_id']);

        $out = [];
        foreach ($connection->fetchAll($select) as $row) {
            $out[(int) $row['parent_id']][] = [
                'required'        => (int) $row['required'],
                'selection_count' => (int) $row['selection_count'],
                'product_ids'     => array_values(array_filter(array_map(
                    'intval',
                    explode(',', (string) $row['product_ids'])
                ))),
                'qty'             => max(1.0, (float) $row['qty']),
            ];
        }

        return $out;
    }

    /**
     * Indexed min/max for the current customer group and website.
     *
     * Read from the index rather than from each product's price model because the
     * price model for `new_bundle` is this project's own copy of the bundle one,
     * and the index is the single value both the listing and this rail must agree
     * on. See the project note about new_bundle having had no index rows at all.
     *
     * @param array<int, int> $ids
     * @return array<int, array{min: float, max: float}>
     */
    private function loadIndexPrices(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $websiteId  = (int) $this->storeManager->getStore()->getWebsiteId();

        $select = $connection->select()
            ->from(
                $this->resource->getTableName('catalog_product_index_price'),
                ['entity_id', 'min_price', 'max_price']
            )
            ->where('entity_id IN (?)', $ids)
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', 0);

        $out = [];
        foreach ($connection->fetchAll($select) as $row) {
            $out[(int) $row['entity_id']] = [
                'min' => (float) $row['min_price'],
                'max' => (float) $row['max_price'],
            ];
        }

        return $out;
    }

    /**
     * Thumbnail URL and regular price for every child referenced by any option.
     *
     * One collection for all children of all bundles on the rail — the alternative
     * is a load per selection, which on four bundles of seven selections is
     * twenty-eight product loads for a decorative image strip.
     *
     * @param array<int, array<int, array<string, mixed>>> $options
     * @return array<int, array{thumb: string, price: float}>
     */
    private function loadChildren(array $options): array
    {
        $childIds = [];
        foreach ($options as $opts) {
            foreach ($opts as $option) {
                foreach ($option['product_ids'] as $childId) {
                    $childIds[$childId] = true;
                }
            }
        }

        if (!$childIds) {
            return [];
        }

        $children = $this->collectionFactory->create();
        $children->addAttributeToSelect(['name', 'small_image', 'thumbnail', 'image', 'price'])
            ->addIdFilter(array_keys($childIds))
            ->addStoreFilter($this->storeManager->getStore());

        $out = [];
        foreach ($children as $child) {
            $out[(int) $child->getId()] = [
                'thumb' => $this->imageHelper->init($child, 'product_small_image')
                    ->keepFrame(false)
                    ->resize(96, 96)
                    ->getUrl(),
                /*
                 * The REGULAR price, deliberately: the index min already reflects
                 * specials and catalog rules, so comparing against `price` is what
                 * turns a child going on special into a real bundle saving.
                 */
                'price' => (float) $child->getData('price'),
                //  For the description fallback below, when a bundle carries no
                //  copy of its own.
                'name'  => trim((string) $child->getName()),
            ];
        }

        return $out;
    }

    /**
     * 16:9 hero image for a bundle card, WITHOUT Magento's white frame.
     *
     * `category_page_grid` is 400x400 in this theme and keepFrame defaults to
     * true, so the resizer pads every non-square source out to a square with
     * white bars baked into the file. Dropped into a 16:9 card the crop then
     * lands on the padding, and a portrait photograph rendered as a narrow strip
     * of image between two white blocks.
     *
     * keepFrame(false) scales to fit with no padding; the CSS `object-fit: cover`
     * on the media box does the cropping, which is where cropping belongs.
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    private function bannerUrl($product): string
    {
        return $this->imageHelper->init($product, 'category_page_grid')
            ->keepFrame(false)
            ->resize(800, 450)
            ->getUrl();
    }

    /**
     * What the same products cost bought separately, at their regular prices.
     *
     * @param array<int, array<string, mixed>> $options
     * @param array<int, array{thumb: string, price: float}> $children
     */
    private function getRegularTotal(array $options, array $children, bool $isKit): float
    {
        /*
         * A single-option bundle is a chooser, not a basket, so there is no
         * "everything separately" total. What IS honest for it: the card quotes
         * "From <min final>", so the strike-through pairs it with "From
         * <min regular>" over the same choices. That pairing can only ever
         * UNDERSTATE a real pick's saving, never overstate it — if child A is
         * 100/100 and child B is 200 struck to 90, the card says 90 was 100
         * (save 10) while picking B actually saves 110. Gated like everything
         * else on the delta being real, so a chooser with no discounted child
         * still shows no strike at all.
         */
        if (!$isKit) {
            $minRegular = null;
            foreach (($options[0]['product_ids'] ?? []) as $childId) {
                $price = $children[$childId]['price'] ?? null;
                if ($price === null || $price <= 0) {
                    continue;
                }
                $minRegular = $minRegular === null ? $price : min($minRegular, $price);
            }

            return (float) ($minRegular ?? 0.0);
        }

        $total = 0.0;
        foreach ($options as $option) {
            $cheapest = null;
            foreach ($option['product_ids'] as $childId) {
                $price = $children[$childId]['price'] ?? null;
                if ($price === null || $price <= 0) {
                    continue;
                }
                $cheapest = $cheapest === null ? $price : min($cheapest, $price);
            }
            if ($cheapest === null) {
                return 0.0;   // incomplete data: no comparison rather than a wrong one
            }
            $total += $cheapest * $option['qty'];
        }

        return $total;
    }

    /**
     * @param array<int, array<string, mixed>> $options
     * @param array<int, array{thumb: string, price: float}> $children
     * @return array<int, string>
     */
    private function thumbsFor(array $options, array $children): array
    {
        $thumbs = [];
        foreach ($this->thumbIds($options) as $childId) {
            if (!isset($children[$childId])) {
                continue;
            }
            $thumbs[] = $children[$childId]['thumb'];
            if (count($thumbs) >= self::THUMB_LIMIT) {
                break;
            }
        }

        return $thumbs;
    }

    /**
     * A kit shows one thumbnail per option; a chooser shows the choices.
     *
     * @param array<int, array<string, mixed>> $options
     * @return array<int, int>
     */
    private function thumbIds(array $options): array
    {
        if (count($options) > 1) {
            return array_values(array_filter(array_map(
                static fn (array $o): ?int => $o['product_ids'][0] ?? null,
                $options
            )));
        }

        return $options[0]['product_ids'] ?? [];
    }

    /**
     * @param array<int, array<string, mixed>> $options
     */
    private function thumbTotal(array $options, bool $isKit): int
    {
        return $isKit ? count($options) : (int) ($options[0]['selection_count'] ?? 0);
    }

    /**
     * First sentence-ish of the description, plain text.
     */
    /**
     * What the bundle contains, in words, for a bundle with no description.
     *
     * Two of the bundles on this catalogue carry neither `description` nor
     * `short_description`, and the card reserved the same slot for them as for
     * the ones that do — so they rendered with a hole between the rating and the
     * price. This fills it from the bundle's OWN CONTENTS rather than inventing
     * marketing copy: every word of it is the names of the products actually in
     * the bundle, so it cannot say anything the basket does not.
     *
     * @param  array<int, array<string, mixed>> $options
     * @param  array<int, array<string, mixed>> $children
     */
    private function describeContents(array $options, array $children): ?string
    {
        $names = [];

        foreach ($this->thumbIds($options) as $childId) {
            $name = trim((string) ($children[$childId]['name'] ?? ''));

            if ($name !== '') {
                $names[] = $name;
            }
        }

        $names = array_values(array_unique($names));

        if (!$names) {
            return null;
        }

        $shown = array_slice($names, 0, 3);
        $more  = count($names) - count($shown);
        $list  = implode(', ', $shown);

        $text = $more > 0
            ? (string) __('Includes %1 and %2 more.', $list, $more)
            : (string) __('Includes %1.', $list);

        return $this->excerpt($text);
    }

    private function excerpt(string $html): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) > 140) {
            $text = rtrim(mb_substr($text, 0, 140), " ,.;:-") . '…';
        }

        return $text;
    }

    /**
     * Cached per store. Bundle composition and price change on reindex, not on
     * request, and this sits on the busiest page on the site.
     *
     * @return array<int, mixed>
     */
    public function getCacheKeyInfo(): array
    {
        return [
            'HM_HOME_BUNDLE_DEALS',
            $this->storeManager->getStore()->getId(),
            (int) ($this->getData('limit') ?: 4),
        ];
    }

    protected function getCacheLifetime(): ?int
    {
        return 3600;
    }
}
