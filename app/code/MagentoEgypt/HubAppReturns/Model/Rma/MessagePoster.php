<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;

/**
 * A customer's reply on their return, as the website's reply form posts it
 * (Vnecoms\VendorsRMA\Controller\Customer\Reply): the request is saved (updated_at, unread for admin
 * and seller), the message stored from the customer to the seller (or the RMA contact), e-mails
 * queued to both sides, rma_request_reply_after dispatched and the seller's panel notified.
 *
 * Checks the website makes only in its templates are enforced here: the return must be the
 * customer's (RmaViewAuthorization::canView) and still open (View::isReplyRma: open, awaiting,
 * being). The text is plain, at most 5000 characters, stored escaped.
 */
class MessagePoster
{
    public const MAX_LENGTH = 5000;

    public function __construct(
        private readonly ReturnReader $reader,
        private readonly RmaConfig $rmaConfig,
        private readonly ResourceConnection $resource,
        private readonly EventManager $eventManager,
        private readonly RequestInterface $httpRequest,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws GraphQlInputException|GraphQlNoSuchEntityException
     */
    public function post(int $customerId, int $requestId, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            throw new GraphQlInputException(__('Write a message.'));
        }
        if (mb_strlen($text) > self::MAX_LENGTH) {
            throw new GraphQlInputException(__('Keep the message under %1 characters.', self::MAX_LENGTH));
        }
        $request = $this->reader->load($customerId, $requestId);
        if ($request === null) {
            throw new GraphQlNoSuchEntityException(__('This return doesn\'t exist.'));
        }
        if (!Vocabulary::acceptsReplies((string) $request->getState())) {
            throw new GraphQlInputException(__('This return is closed, so it can\'t take new messages.'));
        }

        $vendor = $request->getVendorObject();
        $message = [
            'message' => $this->rmaConfig->converText(MessageBody::fromPlainText($text)),
            'attachment' => null,
            'type_reply' => ReturnCreator::CUSTOMER_REPLY,
            'type_send_mail' => ReturnCreator::CUSTOMER_REPLY,
            'from' => (string) $request->getData('customer_name'),
            'to' => (string) ($vendor->getName() ?: $this->rmaConfig->contactsName()),
            'isEdit' => false,
        ];

        $this->eventManager->dispatch('rma_request_prepare_save', ['rma' => $request, 'request' => $this->httpRequest]);
        //  The website's frontend-only observer (Vnecoms\RMA\Observer\SetIsReadCustomer, VendorsRMA
        //  preference) marks the reply unread for admin and seller; the customer has read their own.
        $request->setData('is_admin_read', 0);
        $request->setData('is_vendor_read', 0);
        $request->setData('is_customer_read', 1);

        $connection = $this->resource->getConnection();
        $connection->beginTransaction();
        try {
            $request->save();
            $request->saveMessageObject($message);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->logger->error(sprintf(
                'HubAppReturns: reply on return %d (customer %d) not saved: %s',
                $requestId,
                $customerId,
                $e->getMessage()
            ));
            throw new GraphQlInputException(__('We couldn\'t send the message. Please try again.'));
        }

        try {
            $this->eventManager->dispatch(
                'rma_request_reply_after',
                ['rma' => $request, 'addition_information' => $message]
            );
            if ($vendor->getId()) {
                $this->eventManager->dispatch('vnecoms_vendors_push_notification', [
                    'vendor_id' => $vendor->getId(),
                    'type' => 'rma',
                    'message' => __('RMA #%1 has a new message', '<strong>' . $request->getIncrementId() . '</strong>'),
                    'additional_info' => ['id' => $request->getId()],
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'HubAppReturns: reply on return %d saved, notifications failed: %s',
                $requestId,
                $e->getMessage()
            ));
        }
    }
}
