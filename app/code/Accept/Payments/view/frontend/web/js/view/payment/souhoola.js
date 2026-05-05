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
                type: 'souhoola',
                component: 'Accept_Payments/js/view/payment/method-renderer/souhoola-method'
            }
        );
        return Component.extend({});
    }
);