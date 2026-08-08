<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Plugin\CatalogWidget;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Block\Product\ProductsList;

/**
 * Make a CatalogWidget rail honour its own `products_count`.
 *
 * THE DEFECT IS UPSTREAM, in Magento_ConfigurableProduct's
 * Plugin\CatalogWidget\Block\Product\ProductsListPlugin::afterCreateCollection().
 * That plugin finds the configurable PARENTS of any child product the rail
 * matched and adds them with
 *
 *     $result->addItem($item->load($item->getId()));
 *
 * on a collection whose page size has already been applied. Two consequences,
 * both measured on this storefront:
 *
 *   1. THE COUNT OVERRUNS. "Beauty and Perfumes" is configured for 4 products
 *      and rendered 7; "Popular Products" is configured for 8 and rendered 9.
 *      The SQL is correct — it carries LIMIT 4 — the extra rows are appended in
 *      PHP afterwards. On the homepage that alone made the page 1393px taller
 *      than the design, because two rails silently gained a whole extra row.
 *
 *   2. THE ORDER INVERTS. addItem() runs while the collection is still unloaded,
 *      so the parents occupy _items first and the rail's actual query results are
 *      appended behind them. Rendered order was three Luma hoodies followed by
 *      the beauty products the rail was asked for.
 *
 * Simply truncating to the first N would therefore keep the wrong items. This
 * re-sorts the merged set by entity_id descending — which is the order
 * ProductsList::getBaseCollection() itself declares, and the only ordering the
 * widget promises — and then drops everything past the configured count.
 *
 * Frontend-wide rather than homepage-only: every CatalogWidget rail on the site
 * has the same defect, and a widget that ignores its own configured count is
 * wrong wherever it appears.
 */
class EnforceProductsCount
{
    /**
     * @param ProductsList $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterCreateCollection(ProductsList $subject, Collection $result): Collection
    {
        $limit = (int) $subject->getProductsCount();
        if ($limit < 1) {
            return $result;
        }

        /*
         * getItems() forces the load, so by this point _items holds the parents
         * the upstream plugin injected plus the rows the query returned.
         */
        $items = $result->getItems();
        if (count($items) <= $limit) {
            return $result;
        }

        $ids = array_keys($items);
        rsort($ids, SORT_NUMERIC);

        foreach (array_slice($ids, $limit) as $id) {
            $result->removeItemByKey($id);
        }

        return $result;
    }
}
