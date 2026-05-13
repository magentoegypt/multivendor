<?php
/**
 * Page layout config model.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Page\Group;

class Generic extends AbstractGroup
{
    protected $_template = 'Vnecoms_VendorsCms::app/page/all.phtml';

    /**
     * @var \Vnecoms\VendorsCms\Model\App\Page\Group\Config
     */
    protected $_config;

    /**
     * Retrieve layout select chooser html.
     *
     * @return string
     */
    public function getLayoutsChooser()
    {
        $chooserBlock = $this->_layout->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Layout'
        )->setName(
            'app_instance[<%- data.id %>][pages][layout_handle]'
        )->setId(
            'layout_handle'
        )->setClass(
            'required-entry select'
        )->setExtraParams(
            "onchange=\"AppInstance.loadSelectBoxByType('block_reference', ".
            "this.up('div.pages'), this.value)\""
        );

        return $chooserBlock->toHtml();
    }

    /**
     * Retrieve layout select chooser html.
     *
     * @return string
     */
    public function getPageLayoutsChooser()
    {
        $chooserBlock = $this->_layout->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\PageLayout'
        )->setName(
            'app_instance[<%- data.id %>][pages_layout][layout_handle]'
        )->setId(
            'layout_handle'
        )->setClass(
            'required-entry select'
        )->setExtraParams(
            "onchange=\"AppInstance.loadSelectBoxByType('block_reference', ".
            "this.up('div.pages_layout'), this.value)\""
        );

        return $chooserBlock->toHtml();
    }

    /**
     * Get available options for each group.
     *
     * @return array
     */
    public function getAvailableOptions()
    {
        $options = [
                ['value' => 'all_pages', 'label' => (__('All Pages'))],
                ['value' => 'pages', 'label' => (__('Specified Page'))],
                ['value' => 'pages_layout', 'label' => (__('Page Layouts'))],
        ];

        return $options;
    }

    /**
     * Get layout handle of vendor
     * base from layout tranditional, cms_page_types.xml.
     *
     * @return array
     */
    public function getLayoutHandles()
    {
        $handles = [];
        $handles[] = 'vendor_page';
        $handles = array_merge($handles, $this->getPageLayouts());
        if (!$this->_config) {
            $this->_config = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\VendorsCms\Model\PageType\Config');
        }
        foreach ($this->_config->getPageTypes() as $id => $type) {
            $handles[] = $id;
        }

        return $handles;
    }

    public function isEnabled()
    {
        // TODO: Implement isEnabled() method.
        $result = new \Magento\Framework\DataObject(['is_enabled' => true]);
        $this->_eventManager->dispatch('ves_vendorscms_app_pagegroup_generic', ['result' => $result]);

        return $result->getIsEnabled() && $this->_cmsHelper->isEnabled();
    }

    public function processApp()
    {
        // TODO: Implement processApp() method.
    }

    /**
     * get display on container options for product group.
     *
     * @return array
     */
    public function getDisplayOnContainerOptions()
    {
        $container = [];
        $container['all_pages'] = [
            'label' => 'All Pages',
            'code' => 'all_page',
            'name' => 'all_page',
            'is_anchor_only' => '',
            'product_type_id' => '',
            'layout_handle' => 'vendor_page',
        ];

        $container['pages'] = [
            'label' => 'Specified Pages',
            'code' => 'pages',
            'name' => 'pages',
            'is_anchor_only' => '',
            'product_type_id' => '',
            'layout_handle' => 'pages',
        ];

        return $container;
    }

    /**
     * @return string
     */
    public function getJsonConfig()
    {
        // TODO: Implement getJsonConfig() method.

        return json_encode($this->getConfig());
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $config = [
            'name' => 'name test',
            'template' => $this->getTemplate(),
        ];

        return $config;
    }

    /**
     * @return string
     */
    protected function getTemplate()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Magento\Framework\View\Element\Template')
            ->assign('containers', $this->getDisplayOnContainerOptions())
            ->setLayoutsChooser($this->getLayoutsChooser())
            ->setPageLayoutsChooser($this->getPageLayoutsChooser())

            ->setTemplate($this->_template)->toHtml();
    }

    /**
     * Retrieve page layouts.
     *
     * @return array
     */
    protected function getPageLayouts()
    {
        return [
            '1column',
            '2columns-left',
            '2columns-right',
            '3columns',
        ];
    }
}
