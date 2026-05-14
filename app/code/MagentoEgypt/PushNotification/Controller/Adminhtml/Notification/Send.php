<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\PushNotification\Model\NotificationFactory;
use MagentoEgypt\PushNotification\Model\ResourceModel\Notification as NotificationResource;
use MagentoEgypt\PushNotification\Service\NotificationSender;

class Send extends Action
{
    const ADMIN_RESOURCE = 'MagentoEgypt_PushNotification::manage_notification';

    /**
     * @var NotificationFactory
     */
    protected $notificationFactory;

    /**
     * @var NotificationResource
     */
    protected $notificationResource;

    /**
     * @var NotificationSender
     */
    protected $notificationSender;

    public function __construct(
        Context $context,
        NotificationFactory $notificationFactory,
        NotificationResource $notificationResource,
        NotificationSender $notificationSender
    ) {
        parent::__construct($context);
        $this->notificationFactory = $notificationFactory;
        $this->notificationResource = $notificationResource;
        $this->notificationSender = $notificationSender;
    }

    /**
     * Trigger sending the notification via FCM.
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('notification_id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a notification to send.'));
            return $resultRedirect->setPath('*/*/');
        }

        $notification = $this->notificationFactory->create();
        $this->notificationResource->load($notification, $id);

        if (!$notification->getId()) {
            $this->messageManager->addErrorMessage(__('This notification no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $result = $this->notificationSender->send($notification);
            $this->messageManager->addSuccessMessage(
                __(
                    'Notification "%1" dispatched: %2 recipient(s), %3 delivered, %4 failed.',
                    $notification->getTitle(),
                    $result['recipients'],
                    $result['success'],
                    $result['failure']
                )
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while sending the notification.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['notification_id' => $id]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
