/**
 * Checkout Order Summary line, with the item's SKU (CL036-DEV01.46).
 *
 * Core's Magento_Checkout/js/view/summary/item/details, unchanged, plus getSku().
 * The summary renders TOTALS items, and a totals item has no SKU, so it is read
 * from window.checkoutConfig.quoteItemData by item_id: the quote item's own SKU,
 * which for a configurable is the chosen variant's, as on the cart page.
 *
 * `skuLabel` comes from the layout (translate="true"), so Arabic gets its label
 * server-side without touching the deployed js-translation.json.
 */
define([
    'Magento_Checkout/js/view/summary/item/details'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MagentoEgypt_CheckoutExtend/summary/item/details-sku',
            skuLabel: 'SKU'
        },

        /**
         * @param {Object} item a totals item
         * @returns {String}
         */
        getSku: function (item) {
            var data = (window.checkoutConfig && window.checkoutConfig.quoteItemData) || [],
                i;

            for (i = 0; i < data.length; i++) {
                if (String(data[i]['item_id']) === String(item['item_id'])) {
                    return data[i].sku || '';
                }
            }

            return '';
        }
    });
});
