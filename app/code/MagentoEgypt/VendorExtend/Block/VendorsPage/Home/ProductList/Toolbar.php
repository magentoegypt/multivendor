<?php
namespace MagentoEgypt\VendorExtend\Block\VendorsPage\Home\ProductList;

class Toolbar extends \Vnecoms\VendorsPage\Block\Home\ProductList\Toolbar
{
    /**
     * (non-PHPdoc)
     * @see \Magento\Catalog\Block\Product\ProductList\Toolbar::getPagerHtml()
     */
    public function getPagerHtml()
    {
        return \Magento\Catalog\Block\Product\ProductList\Toolbar::getPagerHtml();
    }

    /**
     * Total number of products in current category.
     *
     * @return int
     */
    public function getTotalNum()
    {
        $listBlock = $this->getLayout()->getBlock('vendors.products.list');

        /*
         * method_exists, not just a null check. The list block's CLASS is swapped
         * in this theme's vendorspage_index_index.xml — Vnecoms' Home\ListProduct
         * builds its collection from the catalog layer with no vendor filter at
         * all, so every seller's grid rendered empty. Product\ListProduct filters
         * on vendor_id and is used instead, but it does not carry
         * getTotalNumberOfProducts(), so calling it blind would fatal the page.
         */
        if ($listBlock && method_exists($listBlock, 'getTotalNumberOfProducts')) {
            return $listBlock->getTotalNumberOfProducts();
        }

        return parent::getTotalNum();
    }
}
