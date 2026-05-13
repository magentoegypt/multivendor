/* eslint-disable dot-notation */
define([
    'Magento_Weee/js/view/checkout/summary/item/price/row_excl_tax'
], function (Component) {
    'use strict';

    return Component.extend({
        /**
         * @param {Object} item
         * @return {Number}
         */
        getUnitPriceExclTax: function (item) {
            var unitPriceExclTax = parseFloat(item['row_total']) / parseFloat(item['qty']);

            if (window.checkoutConfig.getIncludeWeeeFlag) {
                unitPriceExclTax += this.getUnitWeeeTaxExclTax(item);
            }

            return unitPriceExclTax;
        },

        /**
         * @param {Object} item
         * @return {Number}
         */
        getUnitWeeeTaxExclTax: function (item) {
            var totalWeeeTaxExclTaxApplied = 0,
                weeeTaxAppliedAmounts;

            if (item['weee_tax_applied']) {
                weeeTaxAppliedAmounts = JSON.parse(item['weee_tax_applied']);
                weeeTaxAppliedAmounts.forEach(function (weeeTaxAppliedAmount) {
                    totalWeeeTaxExclTaxApplied += parseFloat(Math.max(weeeTaxAppliedAmount.amount, 0));
                });
            }

            return totalWeeeTaxExclTaxApplied;
        }
    });
});
