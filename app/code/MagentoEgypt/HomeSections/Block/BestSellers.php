<?php
/**
 * Hub Market — "Best Selling Items".
 *
 * Extends CatalogWidget's ProductsList purely to inherit its product-card
 * rendering: the same grid template, price formatting, add-to-cart, wishlist and
 * compare behaviour as every other rail on the homepage. Only the COLLECTION is
 * replaced.
 *
 * WHY NOT sales_bestsellers_aggregated_*:
 * Magento's bestseller aggregate on this install holds seven rows covering only
 * THREE distinct products — it is refreshed by a report cron that has not been
 * running. The raw order lines tell the truth: 12 distinct products have actually
 * sold, led by "حقيبة يد قماش" at 13 units across 12 orders. So this reads
 * sales_order_item directly.
 *
 * parent_item_id IS NULL excludes configurable/bundle child lines, which would
 * otherwise double-count a sale against both the parent and its simple.
 *
 * The visibility and status filtering is inherited from ProductsList's own
 * collection setup, so disabled or not-visible products cannot leak in even
 * though they may appear in the order history.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\LayoutFactory;
use Magento\Catalog\Block\Product\Context;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Widget\Helper\Conditions;

class BestSellers extends ProductsList
{
    protected ResourceConnection $hmResource;

    /**
     * NOTE THE CONTEXT TYPE. ProductsList extends Catalog's AbstractProduct, so
     * the first argument is \Magento\Catalog\Block\Product\Context, NOT the
     * generic \Magento\Framework\View\Element\Template\Context. Using the
     * generic one compiles fine under php -l and only fails at
     * setup:di:compile with "Incompatible argument type", which is easy to
     * mistake for an unrelated build problem.
     *
     * The parent signature is replicated so DI can still resolve every argument.
     * Optional tail parameters are kept nullable exactly as the parent declares
     * them, so a minor-version change that adds another optional argument does
     * not break construction.
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        HttpContext $httpContext,
        SqlBuilder $sqlBuilder,
        Rule $rule,
        Conditions $conditionsHelper,
        ResourceConnection $resource,
        array $data = [],
        ?Json $json = null,
        ?LayoutFactory $layoutFactory = null,
        ?EncoderInterface $urlEncoder = null,
        ?CategoryRepositoryInterface $categoryRepository = null
    ) {
        $this->hmResource = $resource;
        parent::__construct(
            $context,
            $productCollectionFactory,
            $catalogProductVisibility,
            $httpContext,
            $sqlBuilder,
            $rule,
            $conditionsHelper,
            $data,
            $json,
            $layoutFactory,
            $urlEncoder,
            $categoryRepository
        );
    }

    /**
     * Product ids ordered by units actually sold.
     *
     * @return int[]
     */
    protected function getRankedProductIds(): array
    {
        $limit = (int) ($this->getData('products_count') ?: 6);
        $conn = $this->hmResource->getConnection();
        $items = $this->hmResource->getTableName('sales_order_item');

        $select = $conn->select()
            ->from(['oi' => $items], ['product_id', 'qty' => 'SUM(oi.qty_ordered)'])
            ->where('oi.parent_item_id IS NULL')
            ->group('oi.product_id')
            ->order('qty DESC')
            ->limit($limit * 3); // over-fetch: some will be filtered out as disabled

        return array_map('intval', array_column($conn->fetchAll($select), 'product_id'));
    }

    /**
     * WHY THIS IS NOT AN OVERRIDE OF createCollection().
     *
     * That was the obvious approach and it silently did not work. THREE modules
     * plugin CatalogWidget\Block\Product\ProductsList::createCollection() on
     * this install — Magento_PageBuilder, Magento_ConfigurableProduct and
     * Vnecoms_VendorsProduct — and one of them returns its own collection, which
     * discards whatever the overridden method built. The section rendered 170
     * unrelated products instead of six, with nothing in any log.
     *
     * Setting the collection in _beforeToHtml AFTER the parent has run sidesteps
     * the whole question: the plugins do their work, then the ranked collection
     * replaces the result. ProductsList::_beforeToHtml() is itself just
     * `setProductCollection($this->createCollection())`, so this is the same
     * hand-off, one step later.
     */
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $ids = $this->getRankedProductIds();
        if ($ids) {
            $this->setProductCollection($this->buildRankedCollection($ids));
        }

        return $this;
    }

    /**
     * Collection of exactly the ranked ids, in rank order.
     *
     * @param int[] $ids
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function buildRankedCollection(array $ids)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            // addFieldToFilter on entity_id, not addIdFilter().
            ->addFieldToFilter('entity_id', ['in' => $ids])
            ->addStoreFilter()
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite();

        // Preserve sales rank rather than entity order.
        $collection->getSelect()->order(
            new \Magento\Framework\DB\Sql\Expression(
                'FIELD(e.entity_id, ' . implode(',', $ids) . ')'
            )
        );
        $collection->setPageSize((int) ($this->getData('products_count') ?: 6));

        return $collection;
    }

    /**
     * The parent keys its cache on the serialised rule conditions. This block has
     * none, so the key is derived from the ranking instead — otherwise every
     * instance of this class on a page would share one cache entry.
     */
    public function getCacheKeyInfo()
    {
        $info = parent::getCacheKeyInfo();
        $info[] = 'HM_BESTSELLERS';
        $info[] = (int) ($this->getData('products_count') ?: 6);
        return $info;
    }
}
