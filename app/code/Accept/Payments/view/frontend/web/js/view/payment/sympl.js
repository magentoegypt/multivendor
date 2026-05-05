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
                type: 'sympl',
                component: 'Accept_Payments/js/view/payment/method-renderer/sympl-method'
            }
        );
        return Component.extend({});
    }
);