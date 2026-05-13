<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\App\Page\Chooser;

/**
 * A chooser for container for widget instances.
 *
 * @method getTheme()
 * @method getArea()
 * @method \Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Container setTheme($theme)
 * @method \Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Container setArea($area)
 */
class Container extends \Magento\Framework\View\Element\Html\Select
{
    /**#@+
     * Frontend page layouts
     */
    const PAGE_LAYOUT_1COLUMN = '1column-center';
    const PAGE_LAYOUT_2COLUMNS_LEFT = '2columns-left';
    const PAGE_LAYOUT_2COLUMNS_RIGHT = '2columns-right';
    const PAGE_LAYOUT_3COLUMNS = '3columns';
    /**#@-*/

    /**
     * @var \Magento\Framework\View\Layout\ProcessorFactory
     */
    protected $_layoutProcessorFactory;

    /**
     * @var \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory
     */
    protected $_themesFactory;

    /**
     * @param \Magento\Framework\View\Element\Context                    $context
     * @param \Magento\Framework\View\Layout\ProcessorFactory            $layoutProcessorFactory
     * @param \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themesFactory
     * @param array                                                      $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\View\Layout\ProcessorFactory $layoutProcessorFactory,
        \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themesFactory,
        array $data = []
    ) {
        $this->_layoutProcessorFactory = $layoutProcessorFactory;
        $this->_themesFactory = $themesFactory;
        parent::__construct($context, $data);
    }

    /**
     * Assign attributes for the HTML select element.
     */
    protected function _construct()
    {
        $this->setName('block');
        $this->setClass('required-entry select');
    }

    /**
     * Add necessary options.
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _beforeToHtml()
    {
        if (!$this->getOptions()) {
            $this->addOption('', __('-- Please Select --'));

            //set options for admin page type
        }

        return parent::_beforeToHtml();
    }

    /**
     * Add page types information to the options.
     *
     * @param array $pageTypes
     */
    protected function _addPageTypeOptions(array $pageTypes)
    {
        $label = [];
        // Sort list of page types by label
        foreach ($pageTypes as $key => $row) {
            $label[$key] = $row['label'];
        }
        array_multisort($label, SORT_STRING, $pageTypes);

        foreach ($pageTypes as $pageTypeName => $pageTypeInfo) {
            $params = [];
            $this->addOption($pageTypeName, $pageTypeInfo['label'], $params);
        }
    }

    /**
     * Retrieve page layouts.
     *
     * @return array
     */
    protected function getPageLayouts()
    {
        return [
            self::PAGE_LAYOUT_1COLUMN,
            self::PAGE_LAYOUT_2COLUMNS_LEFT,
            self::PAGE_LAYOUT_2COLUMNS_RIGHT,
            self::PAGE_LAYOUT_3COLUMNS,
        ];
    }
}
