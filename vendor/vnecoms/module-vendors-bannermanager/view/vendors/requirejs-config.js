/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*eslint no-unused-vars: 0*/
var config = {
    "shim": {
        "bannerGallery": {
            "deps": ["baseImage"]
        }
    },
    "map": {
          '*': {
              "bannerGallery": "Vnecoms_BannerManager/js/banner-gallery",
             /* "productGallery":  "Vnecoms_VendorsProduct/js/product-gallery.js.bk",
              "baseImage":       "Vnecoms_VendorsProduct/catalog/base-image-uploader.js.bk"*/
          }
    }
};
