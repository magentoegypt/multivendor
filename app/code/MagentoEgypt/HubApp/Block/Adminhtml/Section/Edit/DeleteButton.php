<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Block\Adminhtml\Section\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Posts to the delete action (deleteConfirm with a data payload sends a POST),
 * because Delete accepts POST only.
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        $id = $this->getSectionId();
        if (!$id) {
            return [];
        }

        return [
            'label' => __('Delete Section'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s', {\"data\": {}})",
                addslashes((string) __('Delete this Home section? The app stops showing it on its next Home request.')),
                $this->getUrl('*/*/delete', ['section_id' => $id])
            ),
            'sort_order' => 20,
        ];
    }
}
