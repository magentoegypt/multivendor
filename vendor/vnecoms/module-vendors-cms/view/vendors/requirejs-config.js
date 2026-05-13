/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
var config = {
    map: {
        "*": {
            "appInstance" : "Vnecoms_VendorsCms/js/app/app",
            "insertBlock": "Vnecoms_VendorsCms/js/insert_block",
            "cmsVariables": "Vnecoms_VendorsCms/js/variables"
        }
    },
    "shim": {
        "extjs/ext-tree-checkbox":["extjs/ext-tree"],
        "extjs/ext-tree":["prototype"],
    },
    "paths": {
        "CmsfolderTree": "Vnecoms_VendorsCms/js/folder-tree"
    },
    "deps": [
    ]
};
