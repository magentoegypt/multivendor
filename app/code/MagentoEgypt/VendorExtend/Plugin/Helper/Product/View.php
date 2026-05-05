<?php 
namespace MagentoEgypt\VendorExtend\Plugin\Helper\Product;

use Magento\Framework\View\Result\Page as ResultPage;

class View
{
	public function beforeInitProductLayout(
		\Magento\Catalog\Helper\Product\View $subject,
		ResultPage $resultPage, $product, $params = null
	) {
		if($product->getPageLayout() == '3-columns-product') {
			$resultPage->addPageLayoutHandles(['amazon' => 'three'], 'catalog_product_view', false);
		}
		return [$resultPage, $product, $params];
	} 
}