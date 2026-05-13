<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use MagentoEgypt\PushNotification\Controller\Adminhtml\Notification;
use MagentoEgypt\PushNotification\Model\NotificationFactory;

class Edit extends Notification
{
    /**
     * @var NotificationFactory
     */
    protected $notificationFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param NotificationFactory $notificationFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        NotificationFactory $notificationFactory
    ) {
        parent::__construct($context, $resultPageFactory, $coreRegistry);
        $this->notificationFactory = $notificationFactory;
    }

    /**
     * Render edit page.
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('notification_id');
        $model = $this->notificationFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This notification no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->coreRegistry->register('magentoegypt_push_notification', $model);

        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage);
        $resultPage->addBreadcrumb(
            $id ? __('Edit Notification') : __('New Notification'),
            $id ? __('Edit Notification') : __('New Notification')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Push Notifications'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getTitle() : __('New Notification')
        );

        return $resultPage;
    }
}
