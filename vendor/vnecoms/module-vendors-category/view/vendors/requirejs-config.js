/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
          //  "categoryForm": "Vnecoms_VendorsCategory/category/form",
            "vesLoadingPopup": "Vnecoms_VendorsCategory/js/loadingPopup",
            "Magento_Variable/js/variables": "Vnecoms_VendorsProduct/js/components/variables"
        }
    },
    "shim": {
        "extjs/ext-tree-checkbox":["extjs/ext-tree"],
        "extjs/ext-tree":["prototype"],
    }
};

