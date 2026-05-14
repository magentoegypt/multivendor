<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Cron;

use Magento\Framework\Stdlib\DateTime\DateTime;
use MagentoEgypt\PushNotification\Api\Data\NotificationInterface;
use MagentoEgypt\PushNotification\Model\ResourceModel\Notification\CollectionFactory;
use MagentoEgypt\PushNotification\Service\NotificationSender;
use Psr\Log\LoggerInterface;

class SendScheduled
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var NotificationSender
     */
    private $sender;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        NotificationSender $sender,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->sender = $sender;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Send any scheduled notifications whose scheduled_at is due.
     */
    public function execute(): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', NotificationInterface::STATUS_SCHEDULED)
            ->addFieldToFilter('scheduled_at', ['notnull' => true])
            ->addFieldToFilter('scheduled_at', ['lteq' => $this->dateTime->gmtDate()])
            ->setPageSize(50);

        foreach ($collection as $notification) {
            try {
                $this->sender->send($notification);
            } catch (\Throwable $e) {
                $this->logger->error('Scheduled push notification failed', [
                    'notification_id' => $notification->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
