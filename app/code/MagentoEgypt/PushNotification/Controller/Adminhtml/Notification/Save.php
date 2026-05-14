<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\PushNotification\Api\Data\NotificationInterface;
use MagentoEgypt\PushNotification\Model\NotificationFactory;
use MagentoEgypt\PushNotification\Model\ResourceModel\Notification as NotificationResource;

class Save extends Action
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
     * Save the notification.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        $id = (int) $this->getRequest()->getParam('notification_id');
        if (!$id && !empty($data['notification_id'])) {
            $id = (int) $data['notification_id'];
        }
        unset($data['notification_id']);

        $model = $this->notificationFactory->create();

        if ($id) {
            $this->notificationResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This notification no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        if (!empty($data['target_ids']) && is_array($data['target_ids'])) {
            $data['target_ids'] = implode(',', array_map('intval', $data['target_ids']));
        }

        if (empty($data['scheduled_at'])) {
            $data['scheduled_at'] = null;
        }

        if (empty($data['image'])) {
            $data['image'] = null;
        }

        if (!$id) {
            $adminUser = $this->_auth->getUser();
            if ($adminUser) {
                $data['created_by'] = (int) $adminUser->getId();
            }
        }

        if (!isset($data['status']) || $data['status'] === '') {
            $data['status'] = NotificationInterface::STATUS_DRAFT;
        }

        unset($data['recipients_count'], $data['success_count'], $data['failure_count'], $data['sent_at']);

        $model->addData($data);

        try {
            $this->notificationResource->save($model);
            $this->messageManager->addSuccessMessage(__('The notification has been saved.'));
            $this->_getSession()->setFormData(false);

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['notification_id' => $model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the notification.'));
        }

        $this->_getSession()->setFormData($data);
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
