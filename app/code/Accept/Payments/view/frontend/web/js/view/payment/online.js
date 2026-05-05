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
                type: 'online',
                component: 'Accept_Payments/js/view/payment/method-renderer/online-method'
            }
        );
        return Component.extend({});
    }
);