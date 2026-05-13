<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Quote\Ajax;

use Magento\Framework\App\Action\Context;

class UpdateQuote extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * Storage
     *
     * @var \Magento\Framework\Session\StorageInterface
     */
    protected $storage;

    public function __construct(
        Context $context,
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\Session\StorageInterface $storage,
        \Magento\Framework\Data\Form\FormKey\Validator $validator
    ){
        parent::__construct($context);
        $this->_session = $session;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->_resultJsonFactory = $jsonFactory;
        $this->_formKeyValidator = $validator;
        $this->storage = $storage;
    }

    /**
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        return $this->_session->getQuote();
    }

    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $response = [];
        try {
            $request = $this->getRequest();
            $quoteProductData = $this->storage->getData('quotation_product_data') ?: $request->getParam('quotation_product_data', true);

            if ($quoteProductData) {
                $quoteData = [];
                foreach (json_decode($quoteProductData, true) as $fieldName => $productData) {
                    foreach ($productData as $id => $value) {
                        $quoteData[$id][$fieldName] = $value;
                    }
                }

                $this->getQuote()->updateItems($quoteData)->setTotalsCollectedFlag(false)->collectTotals()->save();

                $response = [
                    'error' => false,
                    'message' => 'success',
                ];
                $this->messageManager->addSuccessMessage(__('Quote was updated.'));

            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
            );
            $response = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the quote.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $response = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $resultJson = $this->_resultJsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }
}
