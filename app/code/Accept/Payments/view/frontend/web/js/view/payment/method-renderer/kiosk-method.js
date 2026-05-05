define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (
        Component,
        $,
        additionalValidators,
        url,
        placeOrderAction,
        fullScreenLoader
    ) {
        return Component.extend({
            defaults: {
                template: 'Accept_Payments/payment/kiosk',
                success: false,
                bill_reference: null,
                detail: null
            },
            afterPlaceOrder: function (data, event) {
                var self = this;
                fullScreenLoader.startLoader();
                $.ajax({
                    type: 'POST',
                    url: url.build('accept/methods/kioskmethod'),
                    data: data.toString(),
                    processData: false,
                    success: function (response) {
                        fullScreenLoader.stopLoader();
                        if (response.success) {
                            console.log("afterPlaceOrder:success");
                            console.log(response)
                            self.renderPayment(response);
                        } else {
                            console.log("afterPlaceOrder:error");
                            console.log(response)
                            self.renderErrors(response);
                        }
                    },
                    error: function (response) {
                        console.log("afterPlaceOrder:error");
                        console.log(response)
                        fullScreenLoader.stopLoader();
                        self.renderErrors(response);
                    }
                });
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                if (additionalValidators.validate()) {
                    placeOrder = placeOrderAction(
                        this.getData(),
                        false,
                        this.messageContainer
                    );

                    $.when(placeOrder).done(this.afterPlaceOrder.bind(this));
                    return true;
                }

                return false;
            },
            renderPayment: function (data) {
                fullScreenLoader.stopLoader();
                $('body').css({
                    'overflow': 'hidden'
                });

                $('#kiosk-container').show(250, function(){
                    $('#kiosk-id-container').show(250, function(){
                        $('#kiosk-id-container #kiosk-id').show().html(data.bill_reference);
                    });
                });
            },
            renderErrors: function (data) {
                fullScreenLoader.stopLoader();
                $('body').css({
                    'overflow': 'hidden'
                });

                $('#kiosk-container').show(250, function(){
                    $('#kiosk-errors').show(250, function(){
                        $('#kiosk-errors .errors').show().html(data.detail);
                    });
                });
            },
            getData: function () {
                return {"method": this.item.method};
            },
        });
    }
);
