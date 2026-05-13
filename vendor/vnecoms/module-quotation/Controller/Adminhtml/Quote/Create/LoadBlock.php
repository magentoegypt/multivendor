<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\Result\PageFactory;

class LoadBlock extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->quoteRepository = $quoteRepository;
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create\LoadBlock
     */
    protected function _initQuote(){
        $quoteId = $this->getRequest()->getParam('quote_id');
        $quote = $this->quoteRepository->getById($quoteId);

        $this->coreRegistry->register('current_quote', $quote);
        $this->coreRegistry->register('quote', $quote);

        return $this;
    }

    public function addItems(\Vnecoms\Quotation\Model\Quote $quote, array $items)
    {
        foreach ($items as $productId => $config) {
            $config['qty'] = isset($config['qty']) ? (double)$config['qty'] : 1;
            try {
                $this->addItem($quote, $productId, $config);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                throw new \Exception($e);
            } catch (\Exception $e) {
                return $e;
            }
        }

        return $this;
    }

    /**
     * @param $product
     * @param int $config
     */
    public function addItem(\Vnecoms\Quotation\Model\Quote $quote, $product ,$config = 1)
    {
        if (!is_array($config) && !$config instanceof \Magento\Framework\DataObject) {
            $config = ['qty' => $config];
        }
        $config = new \Magento\Framework\DataObject($config);

        //load product
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            $productId = $product;
            $product = $this->_objectManager->create(
                'Magento\Catalog\Model\Product'
            )->setStoreId(
                $quote->getStoreId()
            )->load(
                $product
            );
            if (!$product->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We could not add a product to quote by the ID "%1".', $productId)
                );
            }
        }

        //add to quote
        $quote->addProduct($product, $config);
    }

    /**
     * Process buyRequest file options of items
     *
     * @param array $items
     * @return array
     */
    protected function _processFiles($items)
    {
        /* @var $productHelper \Magento\Catalog\Helper\Product */
        $productHelper = $this->_objectManager->get('Magento\Catalog\Helper\Product');
        foreach ($items as $id => $item) {
            $buyRequest = new \Magento\Framework\DataObject($item);
            $params = ['files_prefix' => 'item_' . $id . '_'];
            $buyRequest = $productHelper->addParamsToBuyRequest($buyRequest, $params);
            if ($buyRequest->hasData()) {
                $items[$id] = $buyRequest->toArray();
            }
        }
        return $items;
    }

    /**
     * Process request data with additional logic for saving quote
     *
     * @param string $action
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _processData($action = null)
    {
        $state = $this->getRequest()->getParam('action');

        $quote = $this->coreRegistry->registry('current_quote');

        /**
         * Saving quote data
        */
        if ($data = $this->getRequest()->getPost('quote')) {
            $quote->addData($data);
        }

        /**
         * Adding products to quote
         */
        if ($this->getRequest()->has('item') && !$this->getRequest()->getPost('update_items') && !($action == 'save')
        ) {
            $items = $this->getRequest()->getPost('item');
            $items = $this->_processFiles($items);
            $this->addItems($quote, $items);
        }

//         /**
//          * Update quote items
//          */
//         if ($this->getRequest()->getPost('update_items')) {
//             $items = $this->getRequest()->getPost('item', []);
//             $items = $this->_processFiles($items);
//             $this->updateQuoteItems($items);
//         }

        /**
         * Remove quote item
         */
        $removeItemId = (int)$this->getRequest()->getPost('remove_item');
        if ($removeItemId) {
            $quote->removeItem($removeItemId);
        }


        $this->quoteRepository->save($quote->collectTotals());
        $quote->reset();
        return $this;
    }

    /**
     * Loading page block
     *
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $request = $this->getRequest();
        try {
            $this->_initQuote()->_processData();

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, $e->getMessage());
        }

        $asJson = $request->getParam('json');
        $block = $request->getParam('block');

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        if ($asJson) {
            $resultPage->addHandle('quotation_quote_create_load_block_json');
        } else {
            $resultPage->addHandle('quotation_quote_create_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                $resultPage->addHandle('quotation_quote_create_load_block_' . $block);
            }
        }

        $result = $resultPage->getLayout()->renderElement('content');
        if ($request->getParam('as_js_varname')) {
            $this->_objectManager->get('Magento\Backend\Model\Session')->setUpdateResult($result);
            return $this->resultRedirectFactory->create()->setPath('quotation/*/showUpdateResult');
        }
        return $this->resultRawFactory->create()->setContents($result);
    }
}
