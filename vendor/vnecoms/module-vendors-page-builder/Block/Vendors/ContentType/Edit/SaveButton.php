<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Block\Vendors\ContentType\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Save button on edit panel for Content Type
 *
 * @api
 */
class SaveButton implements ButtonProviderInterface
{
    /**
     * Retrieve button data
     *
     * @return array
     */
    public function getButtonData() : array
    {
        return [
            'label' => __('Save'),
            'class' => 'save btn btn-default btn-primary fa fa-check-circle ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 30,
        ];
    }
}
