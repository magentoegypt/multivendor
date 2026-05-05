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
                type: 'valu',
                component: 'Accept_Payments/js/view/payment/method-renderer/valu-method'
            }
        );
        return Component.extend({});
    }
);