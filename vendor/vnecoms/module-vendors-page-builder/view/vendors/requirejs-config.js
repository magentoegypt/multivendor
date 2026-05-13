/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            /* Include our Knockout Sortable wrapper */
            'pagebuilder/ko-dropzone': 'Vnecoms_VendorsPageBuilder/js/resource/dropzone/knockout-dropzone',

            /* Utilities */
            'google-map': 'Magento_PageBuilder/js/utils/map',
            'object-path': 'Vnecoms_VendorsPageBuilder/js/resource/object-path',
            'html2canvas': 'Vnecoms_VendorsPageBuilder/js/resource/html2canvas/html2canvas.min',
            'csso': 'Vnecoms_VendorsPageBuilder/js/resource/csso/csso',
            'categoryCheckboxTree': 'Vnecoms_VendorsPageBuilder/js/category-checkbox-tree'
        }
    },
    shim: {
        'pagebuilder/ko-sortable': {
            deps: ['jquery', 'jquery/ui', 'Magento_PageBuilder/js/resource/jquery-ui/jquery.ui.touch-punch']
        },
        'Magento_PageBuilder/js/resource/jquery/ui/jquery.ui.touch-punch': {
            deps: ['jquery/ui']
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/form/element/abstract': {
                'Vnecoms_VendorsPageBuilder/js/form/element/conditional-disable-mixin': true,
                'Vnecoms_VendorsPageBuilder/js/form/element/dependent-value-mixin': true
            },
            'Magento_Ui/js/lib/validation/validator': {
                'Vnecoms_VendorsPageBuilder/js/form/element/validator-rules-mixin': true
            },
            'mage/validation': {
                'Vnecoms_VendorsPageBuilder/js/system/config/validator-rules-mixin': true
            },
            'Magento_Ui/js/form/form': {
                'Vnecoms_VendorsPageBuilder/js/form/form-mixin': true
            },
            'Vnecoms_VendorsPageBuilder/js/content-type/row/appearance/default/widget': {
                'Vnecoms_VendorsPageBuilder/js/content-type/row/appearance/default/widget-mixin': true
            }
        }
    }
};
