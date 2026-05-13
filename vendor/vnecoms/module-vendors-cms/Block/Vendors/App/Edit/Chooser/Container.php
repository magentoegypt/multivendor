<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser;

/**
 * A chooser for container for widget instances.
 *
 * @method \Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Container setTheme($theme)
 * @method \Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Container setArea($area)
 */
class Container extends \Magento\Framework\DataObject
{
    /**#@+
     * Frontend page layouts
     */
    const PAGE_LAYOUT_1COLUMN = '1column-center';
    /**
     *
     */
    const PAGE_LAYOUT_2COLUMNS_LEFT = '2columns-left';
    /**
     *
     */
    const PAGE_LAYOUT_2COLUMNS_RIGHT = '2columns-right';
    /**
     *
     */
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
     * @var
     */
    protected $_options;

    /**
     * @var \Vnecoms\VendorsCms\Model\App\Page\Group\Config
     */
    protected $pageConfig;

    /**
     * @var
     */
    protected $_theme;

    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Container constructor.
     * @param \Magento\Framework\View\Layout\ProcessorFactory $layoutProcessorFactory
     * @param \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themesFactory
     * @param \Vnecoms\VendorsCms\Model\App\Page\Group\Config $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Layout\ProcessorFactory $layoutProcessorFactory,
        \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themesFactory,
        \Vnecoms\VendorsCms\Model\App\Page\Group\Config $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->_layoutProcessorFactory = $layoutProcessorFactory;
        $this->_themesFactory = $themesFactory;
        $this->pageConfig = $config;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($data);
    }

    /**
     * Add necessary options.
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function getContainerOptions()
    {
        if (!$this->_options) {
            $layoutMergeParams = ['theme' => $this->_getThemeInstance($this->getTheme())];

            foreach ($this->getLayoutHandles() as $layoutHandle) {
                /** @var $layoutProcessor \Magento\Framework\View\Layout\ProcessorInterface */
                $layoutProcessor = $this->_layoutProcessorFactory->create($layoutMergeParams);
                $layoutProcessor->addPageHandles([$layoutHandle]);
                $layoutProcessor->addPageHandles(['default']);
                $layoutProcessor->load();

                $pageLayoutProcessor = $this->_layoutProcessorFactory->create($layoutMergeParams);
                $pageLayouts = $this->getPageLayouts();
                foreach ($pageLayouts as $pageLayout) {
                    $pageLayoutProcessor->addHandle($pageLayout);
                }
                $pageLayoutProcessor->load();

                $containers = array_merge($pageLayoutProcessor->getContainers(), $layoutProcessor->getContainers());
                asort($containers, SORT_STRING);

                $containerData = [];
                foreach ($containers as $containerName => $containerLabel) {
                    $containerData[$containerName] = $containerLabel;
                }
                $this->_options[$layoutHandle] = $containerData;
            }
        }

        return $this->_options;
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
     * get List of layout handles.
     *
     * @return array
     */
    public function getLayoutHandles()
    {
        $layoutHandles = [];
        foreach ($this->pageConfig->getPageGroups() as $group) {
            $groupClass = \Magento\Framework\App\ObjectManager::getInstance()
                ->create($group['class']);
            $handles = $groupClass->getLayoutHandles();

            foreach ($handles as $handle) {
                $layoutHandles[] = $handle;
            }
        }

        return $layoutHandles;
    }

    /**
     * get Theme ID by current theme.
     *
     * @return int
     */
    public function getTheme()
    {
        $customer = $this->getVendor()->getCustomer();
        $storeId = $customer->getStoreId();

        $themeId = $this->scopeConfig->getValue(
            \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $themeId;
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

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    protected function getVendor()
    {
        if (!$this->vendor) {
            $this->vendor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\Vendors\Model\Session')->getVendor();
        }

        return $this->vendor;
    }
}
