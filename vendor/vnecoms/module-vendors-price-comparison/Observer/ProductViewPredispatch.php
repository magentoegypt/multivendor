<?php

namespace Vnecoms\VendorsPriceComparison\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsPriceComparison\Model\Source\Product\Main;

class ProductViewPredispatch implements ObserverInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var \Vnecoms\VendorsPriceComparison\Helper\Data
     */
    protected $helper;

    /**
     * ProductViewPredispatch constructor.
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Vnecoms\VendorsPriceComparison\Helper\Data $helper
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Vnecoms\VendorsPriceComparison\Helper\Data $helper
    ){
        $this->productFactory   = $productFactory;
        $this->redirect         = $redirect;
        $this->helper          = $helper;
    }


    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $controllerAction = $observer->getControllerAction();
        $request = $observer->getRequest();
        $productId = $request->getParam('id');
        $quickview = $request->getParam('quickview');
        $product = $this->productFactory->create()->load($productId);
        if(!$product->getId() || !$product->getData('select_from_product_id') || $quickview) return;

        $mainProductId = $product->getData('select_from_product_id');

        if ($product->getId() == $mainProductId) return;

        $mainProduct = $this->productFactory->create()->load($mainProductId);
        if(!$mainProduct->getId()) return;
        /* $this->redirect->success($mainProduct->getProductUrl()); */
        $controllerAction->getResponse()->setRedirect($mainProduct->getProductUrl());
        $request->setDispatched(true);
        $controllerAction->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
    }
}
