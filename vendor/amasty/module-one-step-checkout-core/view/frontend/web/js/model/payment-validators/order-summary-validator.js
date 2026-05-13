define([
    'jquery',
    'uiRegistry'
], function ($, registry) {
    'use strict';

    return {
        /**
         * Validate Summary order form if automatic update setting is enabled
         *
         * @returns {Boolean}
         */
        validate: function () {
            var forms = $('[data-amcheckout-js="order-form"]'),
                result = true;

            registry.get('checkout.sidebar.summary.cart_items.details', function (details) {
                if (!details.isAutomatically) {
                    return result;
                }

                $.each(forms, function () {
                    if (!$(this).valid()) {
                        result = false;
                    }
                });

                return result;
            });

            return result;
        }
    };
});
