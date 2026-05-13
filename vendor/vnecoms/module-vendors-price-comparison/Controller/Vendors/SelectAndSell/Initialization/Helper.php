<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Controller\Vendors\SelectAndSell\Initialization;

use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory as CustomOptionFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory as ProductLinkFactory;
use Magento\Catalog\Api\ProductRepositoryInterface\Proxy as ProductRepository;
use Magento\Catalog\Model\Product\Initialization\Helper\ProductLinks;
use Magento\Catalog\Model\Product\Link\Resolver as LinkResolver;
use Magento\Framework\App\ObjectManager;

/**
 * Class Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Helper extends \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper
{

    /**
     * Initialize product before saving
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    public function initialize(\Magento\Catalog\Model\Product $product)
    {
        $productData = $this->request->getPost('product', []);

         //unset product id from stock data
        if(isset($productData["stock_data"]["product_id"])){
            unset($productData["stock_data"]["product_id"]);
            unset($productData["stock_data"]["item_id"]);
            unset($productData["stock_data"]["stock_id"]);
            unset($productData["stock_data"]["qty"]);
            unset($productData["stock_data"]["website_id"]);
            unset($productData["stock_data"]["type_id"]);
        }

    
        return $this->initializeFromData($product, $productData);
    }
}
