<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use MagentoEgypt\HubApp\Model\Config\AppConfigReader;

/**
 * Stores > Configuration > Magento Egypt > Hub Market App > Feature Flags.
 *
 * Rows of code / enabled / platform; saved as JSON by ArraySerialized and read
 * by Model\Config\AppConfigReader. A code the app does not know is off; a
 * platform row overrides an "all platforms" row with the same code.
 */
class FeatureFlags extends AbstractFieldArray
{
    private ?OptionsSelect $enabledRenderer = null;

    private ?OptionsSelect $platformRenderer = null;

    protected function _prepareToRender(): void
    {
        $this->addColumn('code', [
            'label' => __('Code'),
            'class' => 'required-entry',
            'style' => 'width:180px',
        ]);
        $this->addColumn('enabled', [
            'label' => __('Enabled'),
            'renderer' => $this->enabledRenderer(),
        ]);
        $this->addColumn('platform', [
            'label' => __('Platform'),
            'renderer' => $this->platformRenderer(),
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Flag');
    }

    /**
     * Mark the saved choice of each select as selected.
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $attributes = [];
        $enabled = (string) $row->getData('enabled');
        $attributes['option_' . $this->enabledRenderer()->calcOptionHash($enabled !== '' ? $enabled : '0')]
            = 'selected="selected"';
        $platform = (string) $row->getData('platform');
        $attributes['option_' . $this->platformRenderer()->calcOptionHash($platform !== '' ? $platform : AppConfigReader::FLAG_PLATFORM_ALL)]
            = 'selected="selected"';
        $row->setData('option_extra_attrs', $attributes);
    }

    private function enabledRenderer(): OptionsSelect
    {
        if ($this->enabledRenderer === null) {
            /** @var OptionsSelect $renderer */
            $renderer = $this->getLayout()->createBlock(
                OptionsSelect::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $renderer->setOptions([
                ['value' => '1', 'label' => __('Yes')],
                ['value' => '0', 'label' => __('No')],
            ]);
            $renderer->setClass('select admin__control-select');
            $this->enabledRenderer = $renderer;
        }

        return $this->enabledRenderer;
    }

    private function platformRenderer(): OptionsSelect
    {
        if ($this->platformRenderer === null) {
            /** @var OptionsSelect $renderer */
            $renderer = $this->getLayout()->createBlock(
                OptionsSelect::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $renderer->setOptions([
                ['value' => AppConfigReader::FLAG_PLATFORM_ALL, 'label' => __('All platforms')],
                ['value' => AppConfigReader::FLAG_PLATFORM_ANDROID, 'label' => __('Android')],
                ['value' => AppConfigReader::FLAG_PLATFORM_IOS, 'label' => __('iOS')],
            ]);
            $renderer->setClass('select admin__control-select');
            $this->platformRenderer = $renderer;
        }

        return $this->platformRenderer;
    }
}
