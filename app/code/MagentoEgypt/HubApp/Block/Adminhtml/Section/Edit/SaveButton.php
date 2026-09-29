<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Block\Adminhtml\Section\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Declared as button "save" in the form XML: the UI form adapter binds that id to save(true).
 */
class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Save Section'),
            'class' => 'save primary',
            'data_attribute' => ['form-role' => 'save'],
            'sort_order' => 90,
        ];
    }
}
