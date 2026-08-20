<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Block\VendorsPage\Home;

use Vnecoms\VendorsProduct\Model\Source\Approval as ProductApproval;

/**
 * The seller's shop page listed NO products, on 5 of 6 vendors.
 *
 * Vnecoms builds the grid from `$layer->getProductCollection()` — the catalog
 * LAYER's collection, which carries no vendor filter at all. The module does have
 * a correctly filtered collection (`getProductCollection()`: vendor_id +
 * approval + visibility), but it is only ever used to COUNT for the "Items (n)"
 * tab. So the tab read 2, 4, 6 while the grid underneath said "We can't find
 * products matching the selection".
 *
 * Why this class rather than a preference onto the module's own
 * Block\Product\ListProduct, which already filters correctly: that class has no
 * `_prepareLayout()` to register the "Items (n)" tab and no
 * `getTotalNumberOfProducts()` for the toolbar. Pointing at it wholesale trades
 * an empty grid for a missing tab. Extending Home\ListProduct keeps both and
 * replaces only the one method that is wrong.
 *
 * The select list mirrors Block\Product\ListProduct exactly — attributes, minimal
 * and final price, tax percents, url rewrites. Dropping any of them gives a grid
 * that renders but with no prices or dead links.
 */
class ListProduct extends \Vnecoms\VendorsPage\Block\Home\ListProduct
{
    /**
     * Vendor-filtered, and nothing to do with the catalog layer.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getProductCollection()
    {
        if ($this->_productCollection === null) {
            $vendor = $this->getVendor();

            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToFilter('vendor_id', $vendor ? (int) $vendor->getId() : 0)
                ->addAttributeToFilter('approval', ProductApproval::STATUS_APPROVED)
                ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->addUrlRewrite()
                ->setVisibility($this->productVisibility->getVisibleInCatalogIds());

            $this->_productCollection = $collection;
        }

        return $this->_productCollection;
    }
}
