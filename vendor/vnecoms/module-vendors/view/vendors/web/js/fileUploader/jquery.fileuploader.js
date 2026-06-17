/**
 * Custom Uploader
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global define, require */

(function (factory) {
  'use strict';
  if (typeof define === 'function' && define.amd) {
    // Register as an anonymous AMD module:
    define([
      'jquery',
      'Vnecoms_Vendors/js/fileUploader/jquery.fileupload-image',
      'Vnecoms_Vendors/js/fileUploader/jquery.fileupload-audio',
      'Vnecoms_Vendors/js/fileUploader/jquery.fileupload-video',
      'Vnecoms_Vendors/js/fileUploader/jquery.iframe-transport',
    ], factory);
  } else if (typeof exports === 'object') {
    // Node/CommonJS:
    factory(
      require('jquery'),
      require('Vnecoms_Vendors/js/fileUploaderjquery.fileupload-image'),
      require('Vnecoms_Vendors/js/fileUploader/jquery.fileupload-audio'),
      require('Vnecoms_Vendors/js/fileUploader/jquery.fileupload-video'),
      require('Vnecoms_Vendors/js/fileUploader/jquery.iframe-transport')
    );
  } else {
    // Browser globals:
    factory(window.jQuery);
  }
})();
