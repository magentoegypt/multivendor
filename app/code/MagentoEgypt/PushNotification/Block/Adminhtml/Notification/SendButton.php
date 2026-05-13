<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Block\Adminhtml\Notification;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SendButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $id = $this->getNotificationId();
        if ($id) {
            $data = [
                'label' => __('Send Now'),
                'class' => 'action-primary',
                'on_click' => sprintf(
                    "confirmSetLocation('%s', '%s')",
                    __('Send this notification to its target audience?'),
                    $this->getUrl('*/*/send', ['notification_id' => $id])
                ),
                'sort_order' => 70,
            ];
        }
        return $data;
    }
}
