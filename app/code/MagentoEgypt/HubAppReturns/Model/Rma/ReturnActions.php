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
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;
use Vnecoms\VendorsRMA\Model\Request;

/**
 * hmCancelReturn and hmEscalateReturn: the website's Cancel button (Vnecoms\VendorsRMA\Controller\
 * Customer\Cancel) and Escalate form (Customer\SaveEscalate), on the customer's own return, by the rules
 * the website's page applies (CustomerActions).
 *
 *  - cancel: rma_request_prepare_save -> status "canceled" -> save (the state follows the status) ->
 *    status history by the customer (e-mails queued to both sides) -> seller panel notification;
 *  - escalate: a message is required and files may come with it, as on the form -> status "awaiting"
 *    ("being" when awaiting already) -> save -> the escalation row (its files moved by Vnecoms, as
 *    Attachments stages them) -> status history -> rma_request_escalate_after -> seller notification.
 *
 * Differences, all deliberate:
 *  - the website's controllers check neither rule (anyone with the link cancels or escalates at any
 *    status); these refuse what the page does not offer, and a return that is not the customer's is
 *    "not found", as for hmReturn;
 *  - the writes run in one transaction;
 *  - is_admin_read / is_vendor_read = 0 and is_customer_read = 1 are set here, as the frontend-only
 *    observer does on the website (see MessagePoster);
 *  - the change is dated now: Vnecoms dates the history and escalation rows with the return's
 *    updated_at as it was loaded, the time of the change before;
 *  - the escalation's text is the app's plain text, stored HTML-escaped (MessageBody::fromPlainText),
 *    since the seller and admin panels print it unescaped.
 */
class ReturnActions
{
    public function __construct(
        private readonly ReturnReader $reader,
        private readonly LabelReader $labels,
        private readonly RmaConfig $rmaConfig,
        private readonly Attachments $attachments,
        private readonly ResourceConnection $resource,
        private readonly EventManager $eventManager,
        private readonly RequestInterface $httpRequest,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws GraphQlInputException|GraphQlNoSuchEntityException
     */
    public function cancel(int $customerId, int $requestId): void
    {
        $request = $this->load($customerId, $requestId);
        if (!CustomerActions::canCancel($this->statusCode($request))) {
            throw new GraphQlInputException(__('This return can\'t be cancelled any more.'));
        }
        $statusId = $this->labels->statusIdByCode(CustomerActions::STATUS_CANCELED, 0);
        if ($statusId <= 0) {
            $this->logger->error('HubAppReturns: no "canceled" RMA status; return ' . $requestId . ' not cancelled');
            throw new GraphQlInputException(__('We couldn\'t cancel the return. Please try again.'));
        }

        $this->changeStatus($request, $statusId);
        $connection = $this->resource->getConnection();
        $connection->beginTransaction();
        try {
            $request->save();
            $request->saveStatusHistoryObject(false);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->logger->error(sprintf(
                'HubAppReturns: return %d (customer %d) not cancelled: %s',
                $requestId,
                $customerId,
                $e->getMessage()
            ));
            throw new GraphQlInputException(__('We couldn\'t cancel the return. Please try again.'));
        }

        $this->notifySeller($request, null);
    }

    /**
     * @param mixed $attachments [HmReturnAttachmentInput] or null
     * @throws GraphQlInputException|GraphQlNoSuchEntityException
     */
    public function escalate(int $customerId, int $requestId, string $text, mixed $attachments = null): void
    {
        $text = trim($text);
        if ($text === '') {
            throw new GraphQlInputException(__('Tell Hub Market what went wrong.'));
        }
        if (mb_strlen($text) > MessagePoster::MAX_LENGTH) {
            throw new GraphQlInputException(__('Keep the message under %1 characters.', MessagePoster::MAX_LENGTH));
        }
        $request = $this->load($customerId, $requestId);
        $state = (string) $request->getData('state');
        $escalated = $this->reader->escalations($requestId) !== [];
        if (!CustomerActions::canEscalate($state, $escalated)) {
            throw new GraphQlInputException($escalated
                ? __('This return has already been escalated to Hub Market.')
                : __('A cancelled return can\'t be escalated.'));
        }
        $files = $this->attachments->check($attachments);
        $statusId = $this->labels->statusIdByCode(CustomerActions::escalationStatus($state), 0);
        if ($statusId <= 0) {
            $this->logger->error('HubAppReturns: no escalation RMA status; return ' . $requestId . ' not escalated');
            throw new GraphQlInputException(__('We couldn\'t escalate the return. Please try again.'));
        }

        $this->changeStatus($request, $statusId);
        $staged = $this->attachments->stage($files);
        $escalation = [
            'message' => $this->rmaConfig->converText(MessageBody::fromPlainText($text)),
            'attachment' => implode(',', $staged),
            'type' => ReturnCreator::CUSTOMER_REPLY,
        ];
        $connection = $this->resource->getConnection();
        $connection->beginTransaction();
        try {
            $request->save();
            $request->saveEscalateObject($escalation);
            $request->saveStatusHistoryObject(false);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->attachments->discard($staged);
            $this->logger->error(sprintf(
                'HubAppReturns: return %d (customer %d) not escalated: %s',
                $requestId,
                $customerId,
                $e->getMessage()
            ));
            throw new GraphQlInputException(__('We couldn\'t escalate the return. Please try again.'));
        }
        $this->attachments->sweep($staged);

        $this->notifySeller($request, $escalation);
    }

    /**
     * @throws GraphQlNoSuchEntityException
     */
    private function load(int $customerId, int $requestId): Request
    {
        $request = $this->reader->load($customerId, $requestId);
        if ($request === null) {
            throw new GraphQlNoSuchEntityException(__('This return doesn\'t exist.'));
        }

        return $request;
    }

    private function statusCode(Request $request): string
    {
        return (string) ($this->labels->statuses(0)[(int) $request->getData('status')]['code'] ?? '');
    }

    /**
     * The website's status change on the loaded return, dated now.
     */
    private function changeStatus(Request $request, int $statusId): void
    {
        $this->eventManager->dispatch('rma_request_prepare_save', ['rma' => $request, 'request' => $this->httpRequest]);
        $request->setData('is_admin_read', 0);
        $request->setData('is_vendor_read', 0);
        $request->setData('is_customer_read', 1);
        //  UTC, as the database session runs; the history and escalation rows copy it.
        $request->setData('updated_at', $this->dateTime->gmtDate('Y-m-d H:i:s'));
        $request->setData('status', $statusId);
    }

    /**
     * After the change is saved; a failing notification does not undo it.
     *
     * @param array<string, mixed>|null $escalation what hmEscalateReturn saved, for rma_request_escalate_after
     */
    private function notifySeller(Request $request, ?array $escalation): void
    {
        try {
            if ($escalation !== null) {
                $this->eventManager->dispatch(
                    'rma_request_escalate_after',
                    ['rma' => $request, 'addition_information' => $escalation]
                );
            }
            $vendor = $request->getVendorObject();
            if ($vendor->getId()) {
                $this->eventManager->dispatch('vnecoms_vendors_push_notification', [
                    'vendor_id' => $vendor->getId(),
                    'type' => 'rma',
                    //  The website's message (Customer\Cancel, Customer\SaveEscalate), grammar corrected.
                    'message' => __(
                        'RMA #%1 has changed its status to %2',
                        '<strong>' . $request->getIncrementId() . '</strong>',
                        $request->getStatusTitle()
                    ),
                    'additional_info' => ['id' => $request->getId()],
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'HubAppReturns: return %s changed, notifications failed: %s',
                (string) $request->getIncrementId(),
                $e->getMessage()
            ));
        }
    }
}
