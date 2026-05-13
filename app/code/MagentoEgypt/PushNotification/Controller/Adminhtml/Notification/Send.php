<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MagentoEgypt\PushNotification\Api\Data\NotificationInterface;
use MagentoEgypt\PushNotification\Model\NotificationFactory;
use MagentoEgypt\PushNotification\Model\ResourceModel\Notification as NotificationResource;

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
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @param Context $context
     * @param NotificationFactory $notificationFactory
     * @param NotificationResource $notificationResource
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        NotificationFactory $notificationFactory,
        NotificationResource $notificationResource,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->notificationFactory = $notificationFactory;
        $this->notificationResource = $notificationResource;
        $this->dateTime = $dateTime;
    }

    /**
     * Trigger sending the notification.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('notification_id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a notification to send.'));
            return $resultRedirect->setPath('*/*/');
        }

        $model = $this->notificationFactory->create();
        $this->notificationResource->load($model, $id);

        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(__('This notification no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model->setStatus(NotificationInterface::STATUS_SENT);
            $model->setSentAt($this->dateTime->gmtDate());
            $this->notificationResource->save($model);
            $this->messageManager->addSuccessMessage(
                __('The notification "%1" has been marked as sent. Configure a push gateway to deliver to devices.', $model->getTitle())
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while sending the notification.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['notification_id' => $id]);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
