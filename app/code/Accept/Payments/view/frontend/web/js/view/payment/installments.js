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
                type: 'installments',
                component: 'Accept_Payments/js/view/payment/method-renderer/installments-method'
            }
        );
        return Component.extend({});
    }
);