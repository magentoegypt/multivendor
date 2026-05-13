<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Quote;

use Magento\Framework\App\Action\Context;

class UpdatePost extends \Magento\Framework\App\Action\Action
{
    protected $_formKeyValidator;

    protected $_session;

    protected $customerSession;

    protected $quoteRepository;

    public function __construct
    (
        Context $context,
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Data\Form\FormKey\Validator $validator
    )
    {
        parent::__construct($context);
        $this->_session = $session;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->_formKeyValidator = $validator;
    }

    public function getQuote()
    {
        return $this->_session->getQuote();
    }

    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $updateAction = (string)$this->getRequest()->getParam('quote_action');

        switch ($updateAction) {
            case 'empty':
                $this->_empty();
                break;
            case 'update':
                $this->_update();
                break;
            case 'submit':
                $this->_submit();
                break;
            default:
                $this->_update();
        }

        return $this->_goBack();
    }

    /**
     *
     */
    protected function _empty()
    {
        try {
            $this->getQuote()->removeAllItems()->collectTotals()->save();
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->messageManager->addError($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addException($exception, __('We can\'t update the quote.'));
        }
    }

    protected function _goBack()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $resultRedirect->setPath('quotation');

        return $resultRedirect;
    }

    protected function _update()
    {
        try {
            $quoteData = $this->getRequest()->getParam('quote');
            if (is_array($quoteData)) {
                $filter = new \Magento\Framework\Filter\LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
                );
                foreach ($quoteData as $index => $data) {
                    if (isset($data['qty'])) {
                        $quoteData[$index]['qty'] = (float) $filter->filter(trim((string)$data['qty']));
                        $quoteData[$index]['price'] = (float) $data['price'];
                    }
                }

                if (!$this->customerSession->getCustomerId() && $this->getQuote()->getCustomerId()) {
                    $this->getQuote()->setCustomerId(null);
                }

              //  $quoteData = $this->getQuote()->suggestItemsQty($quoteData);
                $this->getQuote()->updateItems($quoteData)->setTotalsCollectedFlag(false)->collectTotals()->save();

                $this->messageManager->addSuccess(__('Quote was updated.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t update the quote.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }
    }

    protected function _submit()
    {
        try {
            $quote = $this->quoteRepository->submit($this->getQuote());
            $this->_session->clearQuote()->clearStorage();
            $this->messageManager->addSuccess(__('Quote created successfully.'));
            $this->_redirect('quotation/customer/view',['quote_id' => $quote->getId()]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t submit the quote.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }
    }
}
