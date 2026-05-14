<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MagentoEgypt\PushNotification\Api\Data\NotificationInterface;
use MagentoEgypt\PushNotification\Helper\Config;
use MagentoEgypt\PushNotification\Model\Notification;
use MagentoEgypt\PushNotification\Model\ResourceModel\Device as DeviceResource;
use MagentoEgypt\PushNotification\Model\ResourceModel\Notification as NotificationResource;
use MagentoEgypt\PushNotification\Service\Fcm\Client as FcmClient;
use MagentoEgypt\PushNotification\Service\Recipient\Resolver as RecipientResolver;
use Psr\Log\LoggerInterface;

class NotificationSender
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RecipientResolver
     */
    private $resolver;

    /**
     * @var FcmClient
     */
    private $fcmClient;

    /**
     * @var DeviceResource
     */
    private $deviceResource;

    /**
     * @var NotificationResource
     */
    private $notificationResource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Config $config,
        RecipientResolver $resolver,
        FcmClient $fcmClient,
        DeviceResource $deviceResource,
        NotificationResource $notificationResource,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->resolver = $resolver;
        $this->fcmClient = $fcmClient;
        $this->deviceResource = $deviceResource;
        $this->notificationResource = $notificationResource;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Send a notification to its target audience via FCM.
     *
     * @param Notification $notification
     * @return array {recipients, success, failure}
     * @throws LocalizedException
     */
    public function send(Notification $notification): array
    {
        $payload = $this->buildMessagePayload($notification);

        if (!$this->config->isEnabled()) {
            $notification->setStatus(NotificationInterface::STATUS_SENT);
            $notification->setSentAt($this->dateTime->gmtDate());
            $this->notificationResource->save($notification);
            return ['recipients' => 0, 'success' => 0, 'failure' => 0];
        }

        $collection = $this->resolver->resolve($notification);
        $collection->setPageSize($this->config->getBatchSize());

        $recipients = 0;
        $success = 0;
        $failure = 0;
        $hadFailures = false;

        try {
            $totalPages = $collection->getLastPageNumber();
            for ($page = 1; $page <= $totalPages; $page++) {
                $collection->setCurPage($page);
                $collection->clear();
                foreach ($collection as $device) {
                    $recipients++;
                    $token = (string) $device->getData('device_token');
                    if ($token === '') {
                        continue;
                    }

                    try {
                        $response = $this->fcmClient->send($token, $payload);
                    } catch (\Throwable $e) {
                        $failure++;
                        $hadFailures = true;
                        $this->logger->error('FCM send threw', ['error' => $e->getMessage(), 'token' => $this->shortToken($token)]);
                        continue;
                    }

                    if ($response['success']) {
                        $success++;
                        continue;
                    }

                    $failure++;
                    $hadFailures = true;
                    if (!empty($response['drop_token'])) {
                        $this->deviceResource->deactivateToken($token);
                    }
                    $this->logger->warning('FCM send failed', [
                        'status' => $response['status'],
                        'body'   => $response['body'],
                        'token'  => $this->shortToken($token),
                    ]);
                }
            }
        } catch (LocalizedException $e) {
            throw $e;
        }

        $notification->setRecipientsCount($recipients);
        $notification->setSuccessCount($success);
        $notification->setFailureCount($failure);
        $notification->setStatus(
            $recipients === 0 || $hadFailures && $success === 0
                ? NotificationInterface::STATUS_FAILED
                : NotificationInterface::STATUS_SENT
        );
        $notification->setSentAt($this->dateTime->gmtDate());
        $this->notificationResource->save($notification);

        return ['recipients' => $recipients, 'success' => $success, 'failure' => $failure];
    }

    /**
     * @param Notification $notification
     * @return array
     */
    private function buildMessagePayload(Notification $notification): array
    {
        return [
            'title'      => (string) $notification->getTitle(),
            'body'       => (string) $notification->getMessage(),
            'image'      => $notification->getImage() ? (string) $notification->getImage() : null,
            'action_url' => $notification->getActionUrl() ? (string) $notification->getActionUrl() : null,
            'data'       => [
                'notification_id' => (string) $notification->getId(),
            ],
        ];
    }

    /**
     * @param string $token
     * @return string
     */
    private function shortToken(string $token): string
    {
        return substr($token, 0, 12) . '…';
    }
}
