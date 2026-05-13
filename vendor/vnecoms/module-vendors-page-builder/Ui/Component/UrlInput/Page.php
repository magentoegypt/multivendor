<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Ui\Component\UrlInput;

/** Provides configuration for url input with type CMS page */
class Page implements \Magento\Ui\Model\UrlInput\ConfigInterface
{
    /**
     * @var \Vnecoms\VendorsPageBuilder\Ui\Component\UrlInput\Page\Options
     */
    private $options;

    /**
     * @param \Vnecoms\VendorsPageBuilder\Ui\Component\UrlInput\Page\Options $options
     */
    public function __construct(\Vnecoms\VendorsPageBuilder\Ui\Component\UrlInput\Page\Options $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        return [
            'label' => __('Page'),
            'component' => 'Vnecoms_VendorsPageBuilder/js/form/element/page-ui-select',
            'template' => 'ui/grid/filters/elements/ui-select',
            'disableLabel' => true,
            'filterOptions' => true,
            'chipsEnabled' => true,
            'levelsVisibility' => '1',
            'sortOrder' => 45,
            'multiple' => false,
            'closeBtn' => true,
            'options' => $this->options->toOptionArray(),
            'filterPlaceholder' => __('Page Name'),
            'missingValuePlaceholder' => __('Page with ID: %s doesn\'t exist'),
            'isDisplayMissingValuePlaceholder' => true,
            'isRemoveSelectedIcon' => true,
        ];
    }
}
