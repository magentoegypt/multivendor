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
                type: 'wallet',
                component: 'Accept_Payments/js/view/payment/method-renderer/wallet-method'
            }
        );
        return Component.extend({});
    }
);