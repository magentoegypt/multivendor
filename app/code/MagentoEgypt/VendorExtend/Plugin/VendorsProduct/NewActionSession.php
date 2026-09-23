<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\VendorsProduct;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Vnecoms\Vendors\Model\Session as VendorSession;
use Vnecoms\VendorsProduct\Controller\Catalog\Product\NewAction;

/**
 * Hub Market: the seller "Add product" page stops fataling.
 *
 * Vnecoms' storefront NewAction (marketplace/catalog_product/new) reads
 * `$this->_session->getProductData(true)` whenever no product data was POSTed —
 * i.e. on every normal visit. The admin-area copies of this controller inherit a
 * backend session, but Vnecoms' storefront AbstractAction never sets `_session`,
 * so it is null and the page dies with
 *   Error: Call to a member function getProductData() on null (NewAction.php:73)
 *
 * Give it the same Vnecoms vendor session the storefront Save controller uses
 * (`$this->_session->getVendor()` there). Nothing on the storefront stores
 * product data in it, so getProductData(true) returns null and the page proceeds
 * exactly as Vnecoms intended.
 *
 * `_session` is not a declared property anywhere in the hierarchy, so this sets a
 * dynamic one. That is a PHP 8.2+ deprecation, and this install's bootstrap
 * excludes E_DEPRECATED from error_reporting, so it is silent — the same reason
 * the original undefined read did not throw.
 *
 * `_config` is the same gap one line later (NewAction.php:88 reads
 * `$this->_config->getValue('vendors/catalog/product_edit_tabs_template')`). A scan
 * of every `$this->_…` the controller and its parent use found exactly these two
 * undeclared; everything else is declared and set.
 *
 * No constructor dependencies on purpose: in production mode a plugin class that
 * has none cannot be broken by a stale compiled argument map.
 */
class NewActionSession
{
    /**
     * Runs Vnecoms' NewAction body with one change: `$this->getUrl()` (NewAction.php:100)
     * exists only on the admin-area parent, so the storefront controller fatals when it
     * builds breadcrumbs. The body below is Vnecoms' execute() verbatim, bound to the
     * controller for its protected members, with getUrl() routed to the frontend URL builder.
     */
    public function aroundExecute(NewAction $subject, callable $proceed)
    {
        $objectManager = ObjectManager::getInstance();
        if (!isset($subject->_session)) {
            $subject->_session = $objectManager->get(VendorSession::class);
        }
        if (!isset($subject->_config)) {
            $subject->_config = $objectManager->get(ScopeConfigInterface::class);
        }
        $url = $objectManager->get(\Magento\Framework\UrlInterface::class);

        return \Closure::bind(function () use ($url) {
            if (!$this->getRequest()->getParam('set')) {
                return $this->resultForwardFactory->create()->forward('noroute');
            }
            $product = $this->productBuilder->build($this->getRequest());
            $productData = $this->getRequest()->getPost('product');
            if (!$productData) {
                $sessionData = $this->_session->getProductData(true);
                if (!empty($sessionData['product'])) {
                    $productData = $sessionData['product'];
                }
            }
            if ($productData) {
                $stockData = $productData['stock_data'] ?? [];
                $productData['stock_data'] = $this->stockFilter->filter($stockData);
                $product->addData($productData);
            }
            $this->_eventManager->dispatch('vendor_catalog_product_new_action', ['product' => $product]);

            $resultPage = $this->resultPageFactory->create();
            if ($this->_config->getValue('vendors/catalog/product_edit_tabs_template')
                == \Vnecoms\VendorsProduct\Model\Config\Source\Tab\Template::TEMPLATE_HORIZONTAL_TABS) {
                $resultPage->addHandle('vendors_catalog_product_edit_tabs_horizontal');
            }
            if ($this->getRequest()->getParam('popup')) {
                $resultPage->addHandle(['popup', 'catalog_product_' . $product->getTypeId()]);
            } else {
                $resultPage->addHandle(['catalog_product_' . $product->getTypeId()]);
                $title = $resultPage->getConfig()->getTitle();
                $title->prepend(__("Catalog"));
                $title->prepend(__("Manage Products"));
                $breadCrumbBlock = $resultPage->getLayout()->getBlock('breadcrumbs');
                if ($breadCrumbBlock) {
                    $breadCrumbBlock->addLink(__("Catalog"), __("Catalog"))
                        ->addLink(__("Manage Products"), __("Manage Products"), $url->getUrl('marketplace/catalog_product'))
                        ->addLink(__("New Product"), __("New Product"));
                }
            }
            $block = $resultPage->getLayout()->getBlock('catalog.wysiwyg.js');
            if ($block) {
                $block->setStoreId($product->getStoreId());
            }
            return $resultPage;
        }, $subject, NewAction::class)();
    }
}
