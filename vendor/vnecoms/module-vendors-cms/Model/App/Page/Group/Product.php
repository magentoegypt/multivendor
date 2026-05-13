<?php
/**
 * Page layout config model.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Page\Group;

class Product extends AbstractGroup
{
    protected $_template = 'Vnecoms_VendorsCms::app/page/product.phtml';

    /**
     * @var \Magento\Catalog\Model\Product\TypeFactory
     */
    protected $_typeFactory;

    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $_productHelper;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $event,
        \Vnecoms\VendorsCms\Helper\Data $cmsHelper,
        \Vnecoms\VendorsCms\Model\App\Page\Group\Config $pageGroupConfig,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Catalog\Model\Product\TypeFactory $productType,
        \Vnecoms\VendorsProduct\Helper\Data $productHelper,
        \Magento\Framework\View\Layout $viewLayout
    ) {
        parent::__construct($event, $cmsHelper, $pageGroupConfig, $layout);
        $this->_typeFactory = $productType;
        $this->_productHelper = $productHelper;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        $result = new \Magento\Framework\DataObject(['is_enabled' => true]);
        $this->_eventManager->dispatch('ves_vendorscms_app_pagegroup_product', ['result' => $result]);

        return $result->getIsEnabled() && $this->_cmsHelper->isEnabled();
    }

    /**
     * Get available options for each group.
     *
     * @return array
     */
    public function getAvailableOptions()
    {
        $productsOptions = $this->getProductTypes();
        array_unshift(
            $productsOptions,
            ['value' => 'all_products', 'label' => __('All Product Types')]
        );

        $this->_eventManager->dispatch('get_available_options_product_after', $productsOptions);

        return $productsOptions;
    }

    /**
     * Get Product Types For Select.
     *
     * @return array
     */
    public function getProductTypes()
    {
        $productTypes = [];
        $types = $this->_typeFactory->create()->getTypes();
        uasort(
            $types,
            function ($elementOne, $elementTwo) {
                return ($elementOne['sort_order'] < $elementTwo['sort_order']) ? -1 : 1;
            }
        );

        foreach ($types as $typeId => $type) {
            if (in_array($typeId, $this->_productHelper->getProductTypeRestriction())) {
                continue;
            }

           // $productTypes[$typeId.'_products'] = $type['label'];
            $productTypes[$typeId] = $type['label']; //fixed products type
        }
        $this->_eventManager->dispatch('get_product_types_after', $productTypes);

        return $productTypes;
    }

    /**
     * get display on container options for product group.
     *
     * @return array
     */
    public function getDisplayOnContainerOptions()
    {
        $container = [];
        $container['all_products'] = [
            'label' => 'Products',
            'code' => 'products',
            'name' => 'all_products',
            'layout_handle' => 'catalog_product_view',
            'is_anchor_only' => '',
            'product_type_id' => '',
        ];

        foreach ($this->getProductTypes() as $typeId => $type) {
            $container[$typeId] = [
                'label' => 'Products',
                'code' => 'products',
                'name' => $typeId,
                'layout_handle' => str_replace(
                    '{{TYPE}}',
                    $typeId,
                    'catalog_product_view_type_{{TYPE}}'
                ),
                'is_anchor_only' => '',
                'product_type_id' => $typeId,
            ];
        }

        return $container;
    }

    /**
     * Get all layout Handles.
     *
     * @return array
     */
    public function getLayoutHandles()
    {
        $handles = [];
        $handles[] = 'catalog_product_view';
        foreach ($this->getProductTypes() as $typeId => $type) {
            $handles[] = str_replace(
                '{{TYPE}}',
                $typeId,
                'catalog_product_view_type_{{TYPE}}'
            );
        }

        return $handles;
    }

    public function processApp()
    {
        // TODO: Implement processApp() method.
    }

    /**
     * @return string
     */
    protected function getTemplate()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Magento\Framework\View\Element\Template')
            ->assign('containers', $this->getDisplayOnContainerOptions())
            //->setDisplayOnSelectHtml($this->getDisplayOnSelectHtml())
            //->setRemoveLayoutButtonHtml($this->getRemoveLayoutButtonHtml())

            ->setTemplate($this->_template)->toHtml();
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
