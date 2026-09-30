<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;
use Vnecoms\VendorsRMA\Model\RequestFactory;

/**
 * Files a return exactly as the website's "Request RMA" form does
 * (Vnecoms\VendorsRMA\Controller\Customer\Save), once EligibilityService has checked it:
 *
 *   rma_request_prepare_save (sets the seller from the first line) -> Request::validateItems()
 *   (website quantity formula, one seller, custom refund cap) -> validate() -> save -> address ->
 *   status history -> first message (e-mails queued to customer and admin/seller) -> lines ->
 *   refund amount -> rma_request_save_after -> seller panel notification.
 *
 * Differences, all deliberate:
 *  - the order and lines were checked against the signed-in customer first (the website never does);
 *  - the writes run in one transaction, so a failure leaves no half-filed return;
 *  - is_admin_read / is_vendor_read = 0 are set here because the observer that sets them on the website
 *    (Vnecoms\RMA\Observer\SetIsReadCustomer) is registered for the frontend area only, and
 *    is_customer_read = 1, as the customer has obviously seen what they just filed;
 *  - the message is the app's plain text, stored HTML-escaped (MessageBody::fromPlainText), and the
 *    client address keeps only valid addresses (ReturnInput::clientIp): Vnecoms stores X-Forwarded-For
 *    as sent, and the admin and seller panels print both unescaped;
 *  - the first message's files come with the mutation rather than from separate uploads; they are
 *    checked by the upload's rules and staged where it puts them (Attachments), so Vnecoms' save moves
 *    them as it moves uploaded ones, and a failed save removes them.
 */
class ReturnCreator
{
    /** Vnecoms\RMA\Model\Source\Message\Type::TYPE_REPLY_CUSTOMER (= Email\Type::TYPE_REPLY_CUSTOMER). */
    public const CUSTOMER_REPLY = 'CUSTOMER REPLY';

    /** Vnecoms\RMA\Model\Request::STATUS_PENDING, status 1 ("Open") on a standard install. */
    private const STATUS_PENDING = 'pending';

    public function __construct(
        private readonly EligibilityService $eligibility,
        private readonly RequestFactory $requestFactory,
        private readonly LabelReader $labels,
        private readonly RmaConfig $rmaConfig,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerNameGenerationInterface $customerNames,
        private readonly ResourceConnection $resource,
        private readonly EventManager $eventManager,
        private readonly RequestInterface $httpRequest,
        private readonly RemoteAddress $remoteAddress,
        private readonly LoggerInterface $logger,
        private readonly Attachments $attachments
    ) {
    }

    /**
     * @param array<string, mixed> $input HmCreateReturnInput
     * @return int the new request's id
     * @throws GraphQlInputException
     */
    public function create(int $customerId, int $storeId, array $input): int
    {
        $prepared = $this->eligibility->prepare($customerId, $storeId, $input);
        //  The first message's files, as the form uploads them before it is posted.
        $files = $this->attachments->check($input['attachments'] ?? null);
        $order = $prepared['order'];
        $customerName = $this->customerName($customerId, (string) $order['customer_email']);

        $data = [
            'order_incremental_id' => (string) $order['increment_id'],
            'package_opened' => $prepared['package_opened'],
            'type' => $prepared['type'],
            'reason' => $prepared['reason'],
            'other_reason' => $prepared['other_reason'],
            'tracking_code' => $prepared['tracking_code'],
            'refund_amount_type' => $prepared['refund_amount_type'],
            'refund_custom_amount' => $prepared['refund_custom_amount'] ?? '',
            'order_item_id' => $prepared['items'],
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'customer_email' => (string) $order['customer_email'],
            'ip_address' => ReturnInput::clientIp(
                (string) $this->rmaConfig->getClientIP(),
                (string) $this->remoteAddress->getRemoteAddress()
            ),
            'status' => $this->labels->statusIdByCode(self::STATUS_PENDING, 1),
            'attachment' => null,
        ];

        $request = $this->requestFactory->create();
        $request->setData($data);
        $this->eventManager->dispatch('rma_request_prepare_save', ['rma' => $request, 'request' => $this->httpRequest]);
        $request->setData('is_admin_read', 0);
        $request->setData('is_vendor_read', 0);
        $request->setData('is_customer_read', 1);

        foreach ([$request->validateItems($data['order_item_id']), $request->validate()] as $errors) {
            if ($errors !== true) {
                throw new GraphQlInputException(__('%1', implode(' ', array_map('strval', (array) $errors))));
            }
        }

        //  Written where the website's upload puts them; saving the message moves them (Attachments).
        $staged = $this->attachments->stage($files);
        $message = [
            'message' => $this->rmaConfig->converText(MessageBody::fromPlainText($prepared['comment'])),
            'attachment' => $staged ? implode(',', $staged) : null,
            'type_reply' => self::CUSTOMER_REPLY,
            'type_send_mail' => self::CUSTOMER_REPLY,
            'from' => $customerName,
            'to' => (string) ($this->rmaConfig->contactsName() ?: __('Administrator')),
            'isEdit' => false,
            'isClosed' => false,
        ];

        $connection = $this->resource->getConnection();
        $connection->beginTransaction();
        try {
            $request->save();
            $request->saveAddressObject();
            $request->saveStatusHistoryObject(false);
            $request->saveMessageObject($message);
            $request->saveItemsObject($data['order_item_id']);
            $request->saveAmountRefundObject($data['refund_amount_type'], $data['refund_custom_amount']);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->attachments->discard($staged);
            $this->logger->error(sprintf(
                'HubAppReturns: return for order %s (customer %d) not filed: %s',
                (string) $order['increment_id'],
                $customerId,
                $e->getMessage()
            ));
            throw new GraphQlInputException(__('We couldn\'t file the return. Please try again.'));
        }
        $this->attachments->sweep($staged);

        //  The return exists from here on; a failing notification must not report it as not filed.
        try {
            $this->eventManager->dispatch(
                'rma_request_save_after',
                ['request' => $request, 'addition_information' => $message]
            );
            $vendor = $request->getVendorObject();
            if ($vendor->getId()) {
                $this->eventManager->dispatch('vnecoms_vendors_push_notification', [
                    'vendor_id' => $vendor->getId(),
                    'type' => 'rma',
                    //  The website's message (Vnecoms VendorsRMA Customer\Save), spelling corrected.
                    'message' => __(
                        'A new RMA #%1 has been submitted',
                        '<strong>' . $request->getIncrementId() . '</strong>'
                    ),
                    'additional_info' => ['id' => $request->getId()],
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'HubAppReturns: return %s filed, notifications failed: %s',
                (string) $request->getIncrementId(),
                $e->getMessage()
            ));
        }

        return (int) $request->getId();
    }

    /**
     * The name the website stores (Customer::getName()), else the order e-mail.
     */
    private function customerName(int $customerId, string $fallback): string
    {
        try {
            $name = trim((string) $this->customerNames->getCustomerName($this->customerRepository->getById($customerId)));
        } catch (\Throwable $e) {
            $name = '';
        }

        return $name !== '' ? $name : $fallback;
    }
}
