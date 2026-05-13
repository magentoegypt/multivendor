/**
 * Copyright � 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
var config = {
    "map": {
        '*': {
            "rma":        	'Vnecoms_RMA/js/rma'
        }
    },
    "shim": {
        "jquery/chonsen": ["jquery","jquery/ui"],
        "jquery/colorbox": ["jquery","jquery/ui"]
    },
    "deps": [],
    "paths": {
        "jquery/ui": "jquery/jquery-ui",
        "jquery/chonsen": "Vnecoms_RMA/js/chosen.jquery.min",
        "jquery/colorbox": 'Vnecoms_RMA/js/jquery.colorbox-min'
    }
};
