<?php
/**
 * A product rail must not link to a page that 404s.
 *
 * WHAT THE CLIENT SAW ([CL036-DEV01.22])
 * --------------------------------------
 * "Some products when we access them don't show anything and an error appears."
 * The attached recording clicks "Mimi All-Purpose Short" in the homepage's
 * Popular Products rail and lands on Page not found. That product (WSH09) is a
 * configurable whose vendor approval is PENDING UPDATE, so the storefront is
 * right to refuse it — the bug is that the rail offered it.
 *
 * HOW IT GOT INTO THE RAIL
 * ------------------------
 * Magento\ConfigurableProduct\Plugin\CatalogWidget\Block\Product\
 * ProductsListPlugin::afterCreateCollection() looks up the configurable PARENTS
 * of every product the rail matched and appends them with
 *
 *     $result->addItem($item->load($item->getId()));
 *
 * That bypasses the collection entirely, so none of the conditions the rail was
 * built with survive: not Vnecoms' approval filter (which
 * Vnecoms\VendorsProduct\Block\Product\Widget\ProductsList::createCollection()
 * applies), not the active-vendor filter, not even `status`. Only visibility is
 * checked, and an unapproved product is perfectly visible.
 *
 * So the fix belongs after that plugin, not inside the collection: whatever the
 * rail ends up holding, everything in it has to be a product this storefront
 * would actually serve.
 *
 * ORDERING. sortOrder 10 — after ConfigurableProduct's injection (2) and before
 * HomeSections' EnforceProductsCount (100). Running it last instead would leave
 * the rail SHORT: the count is enforced first, so removing an item afterwards
 * takes the rail below its configured size and puts a gap in the row.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\CatalogWidget;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;

class DropUnservableProducts
{
    public function __construct(
        private readonly StorefrontVisibility $visibility,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param ProductsList $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterCreateCollection(ProductsList $subject, Collection $result): Collection
    {
        /* getItems() forces the load; by here the injected parents are in _items. */
        $items = $result->getItems();
        if (!$items) {
            return $result;
        }

        $ids = array_map('intval', array_keys($items));

        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (\Throwable $e) {
            return $result;
        }

        $keep = array_flip($this->visibility->sellableIds($ids, $storeId));

        foreach ($ids as $id) {
            if (!isset($keep[$id])) {
                $result->removeItemByKey($id);
            }
        }

        return $result;
    }
}
