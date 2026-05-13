/*eslint-disable */
/* jscs:disable */
define(["Vnecoms_VendorsPageBuilder/js/config", "Vnecoms_VendorsPageBuilder/js/utils/image", "Vnecoms_VendorsPageBuilder/js/utils/object", "Vnecoms_VendorsPageBuilder/js/utils/url"], function (_config, _image, _object, _url) {
  /**
   * Copyright © Magento, Inc. All rights reserved.
   * See COPYING.txt for license details.
   */

  /**
   * @api
   */
  var Src = /*#__PURE__*/function () {
    "use strict";

    function Src() {}

    var _proto = Src.prototype;

    /**
     * Convert value to internal format
     *
     * @param value string
     * @returns {string | object}
     */
    _proto.fromDom = function fromDom(value) {
      if (!value) {
        return "";
      }

      return (0, _image.decodeUrl)(value);
    }
    /**
     * Convert value to knockout format
     *
     * @param {string} name
     * @param {DataObject} data
     * @returns {string}
     */
    ;

    _proto.toDom = function toDom(name, data) {
      var value = (0, _object.get)(data, name);

      if (value[0] === undefined || value[0].url === undefined) {
        return "";
      }

      var imageUrl = value[0].url;
      var mediaUrl = (0, _url.convertUrlToPathIfOtherUrlIsOnlyAPath)(_config.getConfig("media_url"), imageUrl);
      var mediaPath = imageUrl.split(mediaUrl);
      return "{{media url=" + mediaPath[1] + "}}";
    };

    return Src;
  }();

  return Src;
});
//# sourceMappingURL=src.js.map