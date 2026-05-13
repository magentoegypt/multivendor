<?php

namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;

class ConfigureProductToAdd extends \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create implements HttpPostActionInterface
{
    /**
     * Ajax handler to response configuration fieldset of composite product in quote
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        // Prepare data
        $productId = (int)$this->getRequest()->getParam('id');

        $configureResult = new \Magento\Framework\DataObject();
        $configureResult->setOk(true);
        $configureResult->setProductId($productId);
        $sessionQuote = $this->_objectManager->get('Vnecoms\Quotation\Model\Backend\Session');
        $configureResult->setCurrentStoreId($sessionQuote->getStore()->getId());
        $configureResult->setCurrentCustomerId($sessionQuote->getCustomerId());

        // Render page
        /** @var \Magento\Catalog\Helper\Product\Composite $helper */
        $helper = $this->_objectManager->get('Magento\Catalog\Helper\Product\Composite');
        return $helper->renderConfigureResult($configureResult);
    }
}
