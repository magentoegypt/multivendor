/*eslint-disable */
/* jscs:disable */
define(["Vnecoms_VendorsPageBuilder/js/utils/loader", "Vnecoms_VendorsPageBuilder/js/content-type/observable-updater-factory", "Vnecoms_VendorsPageBuilder/js/content-type/preview-converter-resolver"], function (_loader, _observableUpdaterFactory, _previewConverterResolver) {
  /**
   * Copyright © Magento, Inc. All rights reserved.
   * See COPYING.txt for license details.
   */

  /**
   * Create new preview instance
   *
   * @param {ContentTypeInterface | ContentTypeCollectionInterface} contentType
   * @param {ContentTypeConfigInterface} config
   * @returns {Promise<Preview | PreviewCollection>}
   */
  function create(contentType, config) {
    return new Promise(function (resolve, reject) {
      (0, _observableUpdaterFactory)(config, _previewConverterResolver).then(function (observableUpdater) {
        (0, _loader)([config.preview_component], function (previewComponent) {
          try {
            var preview = new previewComponent(contentType, config, observableUpdater);
            resolve(preview);
          } catch (error) {
            reject("Error within preview component (" + config.preview_component + ") for " + config.name + ".");
            console.error(error);
          }
        }, function (error) {
          reject("Unable to load preview component (" + config.preview_component + ") for " + config.name + ". Please " + "check preview component exists and content type configuration is correct.");
          console.error(error);
        });
      }).catch(function (error) {
        console.error(error);
        return null;
      });
    });
  }

  return create;
});
//# sourceMappingURL=preview-factory.js.map