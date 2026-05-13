<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Controller\Adminhtml\Notification;

use Magento\Framework\Controller\ResultFactory;
use MagentoEgypt\PushNotification\Controller\Adminhtml\Notification;

class NewAction extends Notification
{
    /**
     * Forward to edit action.
     *
     * @return \Magento\Framework\Controller\Result\Forward
     */
    public function execute()
    {
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('edit');
        return $resultForward;
    }
}
