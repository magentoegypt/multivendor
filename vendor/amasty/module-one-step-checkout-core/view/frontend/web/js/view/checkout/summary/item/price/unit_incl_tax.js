define([
    'Magento_Weee/js/view/checkout/summary/item/price/row_incl_tax'
], function (Component) {
    'use strict';

    return Component.extend({
        /**
         * @param {Object} item
         * @return {Number}
         */
        getUnitPriceInclTax: function (item) {
            var unitPriceInclTax = parseFloat(item['price_incl_tax']);

            if (window.checkoutConfig.getIncludeWeeeFlag) {
                unitPriceInclTax += this.getUnitWeeeTaxInclTax(item);
            }

            return unitPriceInclTax;
        },

        /**
         * @param {Object}item
         * @return {Number}
         */
        getUnitWeeeTaxInclTax: function (item) {
            var totalWeeeTaxInclTaxApplied = 0,
                weeeTaxAppliedAmounts;

            if (item['weee_tax_applied']) {
                weeeTaxAppliedAmounts = JSON.parse(item['weee_tax_applied']);
                weeeTaxAppliedAmounts.forEach(function (weeeTaxAppliedAmount) {
                    totalWeeeTaxInclTaxApplied += parseFloat(Math.max(weeeTaxAppliedAmount['amount_incl_tax'], 0));
                });
            }

            return totalWeeeTaxInclTaxApplied;
        }
    });
});
