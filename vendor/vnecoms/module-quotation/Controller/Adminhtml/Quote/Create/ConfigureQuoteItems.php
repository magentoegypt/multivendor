<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

class ConfigureQuoteItems extends \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create
{
    /**
     * Ajax handler to response configuration fieldset of composite product in quote items
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        // Prepare data
        $configureResult = new \Magento\Framework\DataObject();
   
            $quoteItemId = (int)$this->getRequest()->getParam('id');
            if (!$quoteItemId) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Quote item id is not received.'));
            }

            /**
             * @var \Vnecoms\Quotation\Model\item $quoteItem
             */
            $quoteItem = $this->_objectManager->create('Vnecoms\Quotation\Model\Item')->load($quoteItemId);
            if (!$quoteItem->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Quote item is not loaded.'));
            }

            $configureResult->setOk(true);
            $buyRequest = $quoteItem->getBuyRequest();
            $configureResult->setBuyRequest($buyRequest);
            $configureResult->setCurrentStoreId($quoteItem->getStoreId());
            $configureResult->setProductId($quoteItem->getProductId());
            $sessionQuote = $this->_objectManager->get('Vnecoms\Quotation\Model\Backend\Session');
            $configureResult->setCurrentCustomerId($sessionQuote->getCustomerId());


        // Render page
        /** @var \Magento\Catalog\Helper\Product\Composite $helper */
        $helper = $this->_objectManager->get('Magento\Catalog\Helper\Product\Composite');
        return $helper->renderConfigureResult($configureResult);
    }
}
