define([], function () {
    'use strict';

    return function (Shipping) {
        return Shipping.extend({
            /**
             * Override parent function if isBillingSameAsShipping = false
             * This is need to save the shipping form data when billing form open
             *
             * @returns {Boolean}
             */
            isBillingAddressFormVisible: function () {
                if (window.checkoutConfig.isBillingSameAsShipping) {
                    return this._super();
                }

                this.isUpdateCancelledByBilling = false;

                return this.isUpdateCancelledByBilling;
            }
        });
    };
});
