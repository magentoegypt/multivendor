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
                type: 'getgo',
                component: 'Accept_Payments/js/view/payment/method-renderer/getgo-method'
            }
        );
        return Component.extend({});
    }
);