<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use MagentoEgypt\PushNotification\Model\NotificationFactory;
use MagentoEgypt\PushNotification\Model\ResourceModel\Notification as NotificationResource;

class Delete extends Action
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
     * @param Context $context
     * @param NotificationFactory $notificationFactory
     * @param NotificationResource $notificationResource
     */
    public function __construct(
        Context $context,
        NotificationFactory $notificationFactory,
        NotificationResource $notificationResource
    ) {
        parent::__construct($context);
        $this->notificationFactory = $notificationFactory;
        $this->notificationResource = $notificationResource;
    }

    /**
     * Delete a notification.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('notification_id');

        if ($id) {
            try {
                $model = $this->notificationFactory->create();
                $this->notificationResource->load($model, $id);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This notification no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
                $this->notificationResource->delete($model);
                $this->messageManager->addSuccessMessage(__('The notification has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting the notification.'));
                return $resultRedirect->setPath('*/*/edit', ['notification_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find a notification to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
