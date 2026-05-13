<?php
/**
 * Page layout config model.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Page\Group;

class Specified extends AbstractGroup
{
    protected $_template = 'Vnecoms_VendorsCms::app/page/specified.phtml';

    /**
     * @var \Vnecoms\VendorsCms\Model\App\Page\Group\Config
     */
    protected $_config;

    /**
     * Get available options for each group.
     *
     * @return array
     */
    public function getAvailableOptions()
    {
        if (!$this->_config) {
            $this->_config = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\VendorsCms\Model\PageType\Config');
        }

        $items = [];
        foreach ($this->_config->getPageTypes() as $id => $type) {
            $item['label'] = $type['label'];
            $item['value'] = $type['id'];
            $items[$id] = $item;
        }
        //var_dump($items);die();
        return $items;
        //die();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        // TODO: Implement isEnabled() method.
        $result = new \Magento\Framework\DataObject(['is_enabled' => true]);
        $this->_eventManager->dispatch('ves_vendorscms_app_pagegroup_specified', ['result' => $result]);

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
}
