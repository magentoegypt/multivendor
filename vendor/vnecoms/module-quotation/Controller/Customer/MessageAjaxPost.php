<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Controller\Customer;

use Magento\Framework\Exception\NoSuchEntityException;
use Vnecoms\Quotation\Model\QuoteRepository;
use Magento\Framework\App\Action\Context;
use Vnecoms\Quotation\Helper\Guest;

class MessageAjaxPost extends \Magento\Framework\App\Action\Action
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \Vnecoms\Quotation\Model\MessageFactory
     */
    protected $messageFactory;

    /**
     * @var \Vnecoms\Quotation\Model\Email
     */
    protected $mailer;

    /**
     * @param Context $context
     * @param QuoteRepository $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Vnecoms\Quotation\Model\MessageFactory $messageFactory
     * @param \Vnecoms\Quotation\Model\Email $mailer
     */
    public function __construct(
        Context $context,
        QuoteRepository $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Vnecoms\Quotation\Model\MessageFactory $messageFactory,
        \Vnecoms\Quotation\Model\Email $mailer
    ) {
        parent::__construct($context);
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
        $this->resultRawFactory = $resultRawFactory;
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->messageFactory = $messageFactory;
        $this->mailer = $mailer;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        //$resultRaw = $this->resultRawFactory->create();
        $resultJson = $this->resultJsonFactory->create();
        $response = new \Magento\Framework\DataObject();
        $messagePostData = $this->getRequest()->getPostValue();

        try {

            $quoteId = $this->getRequest()->getPostValue('quote_id',false);
            $quote = $this->quoteRepository->getById($quoteId);
            if ($quote->getCustomerId() != $this->customerSession->getCustomerId()) {
                throw new NoSuchEntityException(__("The quote is not available."));
            }

            if(
                !$this->customerSession->isLoggedIn() &&
                $this->customerSession->getData(Guest::QUOTATION_GUEST_KEY) != $quote->getId()
            ) {
                throw new NoSuchEntityException(__("You are not authorized to send message."));
            }

            $message = $this->messageFactory->create();

            $name = $this->customerSession->isLoggedIn()?$this->customerSession->getCustomer()->getName():$quote->getCustomerName();

            $data = [
                'message' => $messagePostData['message'],
                'name' => $name,
                'user_type' => \Vnecoms\Quotation\Model\Message::TYPE_CUSTOMER,
            ];

            if ($fileUploads = $this->getRequest()->getParam('file_upload', false)) {
                $data['attachments'] = $fileUploads;
            }

            $layout = $this->_view->getLayout();
            $messageModel = $message->setData($data)->setQuote($quote);

            // Save model
            $messageModel->save();

            /* Send notification email*/
            $this->mailer->sendMessageEmailToAdmin($messageModel, $quote);

            $messageHtml = $layout->createBlock('\Vnecoms\Quotation\Block\Customer\Message')
                ->setTemplate('Vnecoms_Quotation::customer/quote/ajax/message.phtml')
                ->setData('message', $messageModel)
                ->toHtml();

            $this->getResponse()->setHeader('Content-type', 'application/json');

            $response->setData([
                'error' => false,
                'html' => $messageHtml
            ]);
        } catch (NoSuchEntityException $exception) {
            $response->setData([
                'message' => $exception->getMessage(),
                'error' => true,
            ]);
        } catch (\Exception $exception) {
            $resultJson->setStatusHeader(
                \Laminas\Http\Response::STATUS_CODE_400,
                \Laminas\Http\AbstractMessage::VERSION_11,
                'Bad Request'
            );

            $response->setData([
                'message' => __('An error occurred'),
                'error' => true,
            ]);
            $this->logger->critical($exception);
        }

        return $this->getResponse()->representJson($response->toJson());
    }
}
