<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Controller\Vendors\SelectAndSell;

use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;

class NewAction extends \Vnecoms\VendorsProduct\Controller\Vendors\Product
{
    /**
     * Array of actions which can be processed without secret key validation
     *
     * @var array
     */
    protected $_publicActions = ['edit'];

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter
     */
    protected $stockFilter;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * NewAction constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter $stockFilter
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder,
        \Magento\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter $stockFilter,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->stockFilter = $stockFilter;
        parent::__construct($context, $productBuilder);

        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;

    }

    /**
     * Product edit form
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $productId = (int) $this->getRequest()->getParam('id');
        $product = $this->productBuilder->build($this->getRequest());

        if (
            $productId &&
            !$product->getId()
        ) {
            $this->messageManager->addError(__('This product no longer exists.'));
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
        $this->_coreRegistry->register('select_and_sell', true);
        $product->setData('select_from_product_id',true);
        //  $product->setStatus(0);
        $product->setData('sku',null);
        //$product->setData('tax_class_id',0);
        $product->setData('special_price',null);
        $product->setData('special_from_date',null);
        $product->setData('special_to_date',null);
        $product->setData('cost',null);
        $product->setData('tier_price',null);
        $product->setData('msrp',null);
        $product->setData('msrp_display_actual_price_type',0);
        $product->setData('qty',null);
        $product->setData('quantity_and_stock_status',null);
        $product->setData('url_key',$product->getUrlKey().'-'.time());
        $options = $product->getOptions();
        if ($options) {
            $options = array_map(function ($option) {
                /** @var \Magento\Catalog\Model\Product\Option $option */
                $option->setOptionId(null);
                $option->setProductId(null);
                return $option;
            }, $options);
        }
        $product->setOptions($options);


        $this->_eventManager->dispatch('vendor_catalog_product_edit_action', ['product' => $product]);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->addHandle('catalog_product_' . $product->getTypeId());

        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__("Catalog"));
        $title->prepend(__("Manage Products"));
        $title->prepend($product->getName());
        $breadCrumbBlock = $resultPage->getLayout()->getBlock('breadcrumbs');
        $breadCrumbBlock->addLink(__("Catalog"), __("Catalog"))
            ->addLink(__("Manage Products"), __("Manage Products"),$this->getUrl('catalog/product'))
            ->addLink($product->getName(), $product->getName());

        if (!$this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->isSingleStoreMode()
            &&
            ($switchBlock = $resultPage->getLayout()->getBlock('store_switcher'))
        ) {
            $switchBlock->setDefaultStoreName(__('Default Values'))
                ->setWebsiteIds($product->getWebsiteIds())
                ->setSwitchUrl(
                    $this->getUrl(
                        '*/*/*',
                        ['_current' => true, 'active_tab' => null, 'tab' => null, 'store' => null]
                    )
                );
        }

        $block = $resultPage->getLayout()->getBlock('catalog.wysiwyg.js');
        if ($block) {
            $block->setStoreId($product->getStoreId());
        }

        return $resultPage;
    }
}
