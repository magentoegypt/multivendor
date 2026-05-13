<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Source;

use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;

/**
 * Class Page.
 */
class Container implements \Magento\Framework\Option\ArrayInterface
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
     * @var array
     */
    protected $options;

    /**
     * @var \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory
     */
    protected $_themesFactory;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @var \Magento\Framework\View\Layout\ProcessorFactory
     */
    protected $_layoutProcessorFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\Framework\View\Layout\ProcessorFactory $processorFactory
    ) {
        $this->_themesFactory = $collectionFactory;
        $this->_layoutProcessorFactory = $processorFactory;
    }

    /**
     * To option array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $layoutMergeParams = ['theme' => $this->_getThemeInstance('2')];
            /** @var $layoutProcessor \Magento\Framework\View\Layout\ProcessorInterface */
            $layoutProcessor = $this->_layoutProcessorFactory->create($layoutMergeParams);
            $layoutProcessor->addPageHandles(['default']);
            $layoutProcessor->load();

            $pageLayoutProcessor = $this->_layoutProcessorFactory->create($layoutMergeParams);
            $pageLayouts = $this->getPageLayouts();
            foreach ($pageLayouts as $pageLayout) {
                $pageLayoutProcessor->addHandle($pageLayout);
            }
            $pageLayoutProcessor->load();

            $containers = array_merge($pageLayoutProcessor->getContainers(), $layoutProcessor->getContainers());
            if ($this->getAllowedContainers()) {
                foreach (array_keys($containers) as $containerName) {
                    if (!in_array($containerName, $this->getAllowedContainers())) {
                        unset($containers[$containerName]);
                    }
                }
            }
            asort($containers, SORT_STRING);
        }

        return $this->options;
    }

    /**
     * Retrieve theme instance by its identifier.
     *
     * @param int $themeId
     *
     * @return \Magento\Theme\Model\Theme|null
     */
    protected function _getThemeInstance($themeId)
    {
        /** @var \Magento\Theme\Model\ResourceModel\Theme\Collection $themeCollection */
        $themeCollection = $this->_themesFactory->create();

        return $themeCollection->getItemById($themeId);
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
