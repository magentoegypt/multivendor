<?php

namespace Vnecoms\VendorsCms\Block\Vendors\Helper\Renderer;

class Type extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    public function getElementHtml()
    {
        $html = '';
        $html .= '<script id="contenttype-content-layout-items" type="text/x-magento-template">
                    <div class="stand column-dropable">
                        <div class="contenttype-layout-items-block column-draggable layout-item" id="layout-item-block">
                            <label class="column-handler">CMS Block</label>
                        </div>
                    </div>
                    <div class="stand column-dropable">
                        <div class="contenttype-layout-items-field column-draggable layout-item" id="layout-item-field">
                            <label class="column-handler">Field</label>
                        </div>
                    </div>
                    <div class="stand column-dropable">
                        <div class="contenttype-layout-items-group column-draggable layout-item" id="layout-item-group">
                            <label class="column-handler">Group</label>
                        </div>
                    </div>
                </script>';

        $html .= '<div class="admin__field field field-contenttype-layout-items">';
        $html .= '<div class="stand column-dropable ui-sortable">
                    <div class="contenttype-layout-items-block column-draggable layout-item" id="layout-item-block">
                        <label class="column-handler">CMS Block</label>
                    </div>
                </div>';
        $html .= '<div class="stand column-dropable ui-sortable">
                    <div class="contenttype-layout-items-field column-draggable layout-item" id="layout-item-field">
                        <label class="column-handler">Field</label>
                    </div>
                </div>';
        $html .= '<div class="stand column-dropable ui-sortable">
                    <div class="contenttype-layout-items-group column-draggable layout-item" id="layout-item-group">
                        <label class="column-handler">Group</label>
                    </div>
                </div>';
        $html .= '</div>';

        return $html;
    }
}
