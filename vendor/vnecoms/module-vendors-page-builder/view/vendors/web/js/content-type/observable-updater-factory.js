/*eslint-disable */
/* jscs:disable */
define(["Vnecoms_VendorsPageBuilder/js/converter/converter-pool-factory", "Vnecoms_VendorsPageBuilder/js/mass-converter/converter-pool-factory", "Vnecoms_VendorsPageBuilder/js/content-type/observable-updater"], function (_converterPoolFactory, _converterPoolFactory2, _observableUpdater) {
  /**
   * Copyright © Magento, Inc. All rights reserved.
   * See COPYING.txt for license details.
   */

  /**
   * Create new observable updater instance
   *
   * @param {ContentTypeConfigInterface} config
   * @param {Function} converterResolver
   * @returns {Promise<ObservableUpdater>}
   */
  function create(config, converterResolver) {
    var promises = [(0, _converterPoolFactory)(config.name), (0, _converterPoolFactory2)(config.name)];
    return new Promise(function (resolve) {
      Promise.all(promises).then(function (resolvedPromises) {
        var converterPool = resolvedPromises[0],
            massConverterPool = resolvedPromises[1];
        resolve(new _observableUpdater(converterPool, massConverterPool, converterResolver));
      }).catch(function (error) {
        console.error(error);
        return null;
      });
    });
  }

  return create;
});
//# sourceMappingURL=observable-updater-factory.js.map