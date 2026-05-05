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
                type: 'kiosk',
                component: 'Accept_Payments/js/view/payment/method-renderer/kiosk-method'
            }
        );
        return Component.extend({});
    }
);