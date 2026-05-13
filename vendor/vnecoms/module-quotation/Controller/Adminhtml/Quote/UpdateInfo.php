<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\App\Action\Context;
use Vnecoms\Quotation\Model\Quote;

class UpdateInfo extends \Magento\Backend\App\Action implements HttpPostActionInterface
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Vnecoms_Quotation::save';


    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Vnecoms\Quotation\Model\Email
     */
    protected $mailer;

    /**
     * UpdateInfo constructor.
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository
     * @param \Vnecoms\Quotation\Model\Email $mailer
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository,
        \Vnecoms\Quotation\Model\Email $mailer
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->mailer = $mailer;
        parent::__construct($context);
    }

    /**
     * @return \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create\LoadBlock
     */
    protected function _initQuote(){
        $quoteId = $this->getRequest()->getParam('quote_id');
        $quote = $this->quoteRepository->getById($quoteId);

        return $quote;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            try {
                $quote = $this->_initQuote();
                $field = $this->getRequest()->getParam('field');
                $fieldValue = $this->getRequest()->getParam($field);
                if($field){
                    $quote->setData($field, $fieldValue);
                }
                $this->quoteRepository->save($quote);


                if ($field == "status") {
                    switch ($fieldValue) {
                        case Quote::STATUS_HOLD:
                            $this->mailer->sendHeldQuoteEmailToCustomer($quote);
                            break;
                        case Quote::STATUS_CANCELLED:
                            $this->mailer->sendCancelledQuoteEmailToCustomer($quote);
                            break;
                        case Quote::STATUS_SENT:
                            $this->mailer->sendApprovedQuoteEmailToCustomer($quote);
                            break;
                        case Quote::STATUS_REJECTED:
                            $this->mailer->sendRejectedQuoteEmailToCustomer($quote);
                            break;
                    }
                }

                $response = [
                    'error' => false,
                ];
            } catch (LocalizedException $e) {
                $response = [
                    'error' => true,
                    'message' => $e->getMessage()
                ];
            } catch (\Exception $e) {
                $response = [
                    'error' => true,
                    'message' => __('Something went wrong while saving the Quote.')
                ];
            }
        }else{
            $response = [
                'error' => true,
                'message' => __("The request is not valid.")
            ];
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }
}
