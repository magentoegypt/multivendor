<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Algolia\SearchAdapter\ViewModel\Sorter;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Search\Model\EngineResolver;

/**
 * On the search results page the toolbar's sort AND paging were ignored
 * (2026-09-24, DEV06 "filters and sorting work correctly"): "Lowest price" /
 * "Highest price" / "Newest" returned relevance order, and pages 2, 3... showed
 * the same products as page 1.
 *
 * Category pages are fine: the toolbar puts the composite sort ("price~asc"),
 * page size and current page on the collection before it loads, and the Algolia
 * adapter turns them into one query on the sort replica
 * (hubmarket_en_products_price_default_asc, hitsPerPage 12, page n). The search
 * page is different: Magento_CatalogSearch::result.phtml calls
 * getResultCount() -> getSize() BEFORE the product list and its toolbar render,
 * so the one and only Algolia query ran with no order and no paging (primary
 * index, hitsPerPage 10000, page 0) and the page reused that result. The
 * adapter's SortState fallback does not help: QueryMapper only consults it for
 * request objects without getSort().
 *
 * So, once the search layer and the layout exist and before any block renders,
 * put the toolbar's state on the layer's product collection exactly as
 * Toolbar::setCollection() will: current page, page size (the toolbar block's
 * own validated limit), and the chosen sort unless it is relevance. The later
 * setCollection() call sets the same values again. OpenSearch stores and every
 * other page are left alone.
 *
 * No constructor dependencies on purpose (compiled DI, see AddPriceRange).
 */
class ApplySearchToolbarEarly implements ObserverInterface
{
    /**
     * Search results page, and category pages since the category hero band
     * shows the listing's total and so counts the results before the list
     * renders too (QA01 2026-09-25, BUG-05).
     */
    private const ACTIONS = ['catalogsearch_result_index', 'catalog_category_view'];
    private const TOOLBAR_BLOCK = 'product_list_toolbar';

    public function execute(Observer $observer): void
    {
        if (!in_array($observer->getData('full_action_name'), self::ACTIONS, true)) {
            return;
        }

        $om = ObjectManager::getInstance();

        if ($om->get(EngineResolver::class)->getCurrentSearchEngine() !== 'algolia') {
            return;
        }

        try {
            $collection = $om->get(LayerResolver::class)->get()->getProductCollection();
        } catch (\Throwable $e) {
            return; // No search layer on this request: nothing to apply.
        }

        $layout = $observer->getData('layout');
        $toolbar = $layout ? $layout->getBlock(self::TOOLBAR_BLOCK) : false;

        if ($toolbar) {
            $collection->setCurPage((int) $toolbar->getCurrentPage());
            $limit = (int) $toolbar->getLimit();
            if ($limit > 0) {
                $collection->setPageSize($limit);
            }
        }

        $order = (string) $om->get(ToolbarMemorizer::class)->getOrder();
        $parts = explode(Sorter::SORT_PARAM_DELIMITER, $order);

        if (count($parts) >= 2 && $order !== Sorter::SORT_PARAM_DEFAULT) {
            $collection->setOrder($order, strtolower($parts[1]) === 'desc' ? 'desc' : 'asc');
        }
    }
}
