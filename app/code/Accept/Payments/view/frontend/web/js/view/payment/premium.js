define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'premium',
                component: 'Accept_Payments/js/view/payment/method-renderer/premium-method'
            }
        );
        return Component.extend({});
    }
);