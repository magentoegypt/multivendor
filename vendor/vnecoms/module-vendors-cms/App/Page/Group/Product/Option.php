<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\App\Page\Group\Product;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive.
 */
class Option implements OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\Product\TypeFactory
     */
    protected $_typeFactory;

    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $_productHelper;

    public function __construct(
        \Magento\Catalog\Model\Product\TypeFactory $productType,
        \Vnecoms\VendorsProduct\Helper\Data $productHelper
    ) {
    
        $this->_typeFactory = $productType;
        $this->_productHelper = $productHelper;
    }

    public function toOptionArray()
    {
        // TODO: Implement toOptionArray() method.
        return $this->getAvailableOptions();
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

        return $productsOptions;
    }

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

            $productTypes[$typeId] = $type['label'];
        }

        return $productTypes;
    }

    public function getHtmlTemplate()
    {
        $html = '';
        foreach ($this->getDisplayOnContainerOptions() as $container) {
            $html .= '<div class="no-display '.$container['code'].' group_container" id="'.$container['name'].'_<%- data.id %>">';
            $html .= '<input disabled="disabled" type="hidden" class="container_name" name="__[container_name]" value="widget_instance[<%- data.id %>]['.$container['name'].']" />';
            $html .= '<input disabled="disabled" type="hidden" name="widget_instance[<%- data.id %>]['.$container['name'].'][page_id]" value="<%- data.page_id %>" />';
            $html .= '<input disabled="disabled" type="hidden" class="layout_handle_pattern" name="widget_instance[<%- data.id %>]['.$container['name'].'][layout_handle]" value="'.$container['layout_handle'].'" />';
            $html .= '<table class="data-table">';
            $html .= '<col width="200" />';
            $html .= '<thead>';
            $html .= '<tr>'.
            '<th><label>'.$container['label'].'</label></th>'.
            '<th><label>'.__('Container').' <span class="required">*</span></label></th>'.
            '</tr>'.
            '</thead>'.
            '<tbody>'.
            '<tr>'.
            '<td>'.
            '<input disabled="disabled" type="radio" class="radio for_all" id="all_'.$container['name'].'_<%- data.id %>" name="widget_instance[<%- data.id %>]['.$container['name'].'][for]" value="all" onclick="WidgetInstance.togglePageGroupChooser(this)" checked="checked" />&nbsp;'.
            '<label for="all_'.$container['name'].'_<%- data.id %>">'.__('All').'</label><br />'.
            '<input disabled="disabled" type="radio" class="radio for_specific" id="specific_'.$container['name'].'_<%- data.id %>" name="widget_instance[<%- data.id %>]['.$container['name'].'][for]" value="specific" onclick="WidgetInstance.togglePageGroupChooser(this)" />&nbsp;'.
            '<label for="specific_'.$container['name'].'_<%- data.id %>">'.__('Specific %1', $container['label']).'</label>'.
            '</td>'.
            '<td>'.
            '<div class="block_reference_container">'.
            '<div class="block_reference"></div>'.
            '</div>'.
            '</td>'.
            '</tr>'.
            '</tbody>'.
            '</table>'.
            '<div class="no-display chooser_container" id="'.$container['name'].'_ids_<%- data.id %>">'.
            '<input disabled="disabled" type="hidden" class="product_type_id" name="widget_instance[<%- data.id %>]['.$container['name'].'][product_type_id]" value="'.$container['product_type_id'].'" />'.
            '<p>'.
            '<input disabled="disabled" type="text" class="input-text entities" name="widget_instance[<%- data.id %>]['.$container['name'].'][entities]" value="<%- data.'.$container['name'].'_entities %>" readonly="readonly" />&nbsp;'.
            '<a class="widget-option-chooser" href="javascript:void(0)" onclick="WidgetInstance.displayEntityChooser(\''.$container['code'].'\', \''.$container['name'].'_ids_<%- data.id %>\')"  title="'.__('Open Chooser').'">'.
            '<img src="" alt="'.__('Open Chooser').'" />'.
            '</a>&nbsp;'.
            '<a href="javascript:void(0)" onclick="WidgetInstance.hideEntityChooser(\''.$container['name'].'_ids_<%- data.id %>\')" title="'.__('Apply').'">'.
            '<img src="" alt="'.__('Apply').'" />'.
            '</a>'.
            '</p>'.
            '<div class="chooser"></div>'.
            '</div>'.
            '</div>';
        }

        return $html;
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
            'pass_step' => false,
        ];

        foreach ($this->getProductTypes() as $typeId => $type) {
            $container[$typeId] = [
                'label' => 'Products',
                'code' => 'products',
                'name' => $typeId.'_products',
                'layout_handle' => str_replace(
                    '{{TYPE}}',
                    $typeId,
                    'catalog_product_view_type_{{TYPE}}'
                ),
                'is_anchor_only' => '',
                'product_type_id' => $typeId,
                'pass_step' => false,
            ];
        }

        return $container;
    }
}
