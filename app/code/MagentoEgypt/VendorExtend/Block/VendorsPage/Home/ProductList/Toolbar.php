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
        if (!empty($listBlock)) {
            return $listBlock->getTotalNumberOfProducts();
        }
        return parent::getTotalNum();
    }
}
