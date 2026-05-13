define([
    'Magento_Ui/js/lib/view/utils/async',
    'uiComponent',
    'uiRegistry',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'ko',
    'underscore',
    'mage/storage',
    'Amasty_CheckoutGiftWrap/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Ui/js/model/messageList',
    'jquery'
], function (
    $,
    Component,
    registry,
    modal,
    $t,
    ko,
    _,
    storage,
    resourceUrlManager,
    quote,
    errorProcessor,
    fullScreenLoader,
    messagesList
) {
    'use strict';

    var popUp = null;

    return Component.extend({
        defaults: {
            listens: {
                'isFormPopUpVisible': 'popUpVisibleObserver'
            },
            template: 'Amasty_CheckoutGiftWrap/form/gift_messages/container'
        },
        checkbox: null,
        isFormPopUpVisible: ko.observable(false),
        savedData: null,

        /**
         * @param {Object} config
         *
         * @returns {*}
         */
        initConfig: function (config) {
            var self = this;

            this._super();

            registry.get(this.name + '.checkbox', function (checkbox) {
                self.checkbox = checkbox;
                checkbox.checked.subscribe(self.toggleState.bind(self));
                checkbox.on('edit_link_click', self.showPopup.bind(self));
            });

            return this;
        },

        /**
         * @param {boolean} checked
         *
         * @returns {void}
         */
        toggleState: function (checked) {
            if (checked) {
                this.showPopup();
                this.submit(true);
            } else {
                this.delete();
            }
        },

        /**
         * @param {*} value
         *
         * @returns {*}
         */
        popUpVisibleObserver: function (value) {
            if (value) {
                this.getPopUp().openModal();
            }

            return this;
        },

        /**
         * @returns {void}
         */
        showPopup: function () {
            this.isFormPopUpVisible(true);
        },

        /**
         * @returns {void}
         */
        delete: function () {
            var self = this,
                data = [];

            ['item_messages', 'quote_message'].forEach(function (containerName) {
                // eslint-disable-next-line vars-on-top
                var container = self.getChild(containerName);

                if (typeof(container) === 'undefined')
                    return;

                container.elems().forEach(function (messageComponent) {
                    data.push({
                        item_id: messageComponent.item_id,
                        recipient: "",
                        sender: "",
                        message: ""
                    });
                })
            });

            this.saveGiftData(data);
        },

        /**
         * @param {Event|bool} initial
         *
         * @returns {void}
         */
        submit: function (initial) {
            var self = this,
                data = [],
                request;

            ['item_messages', 'quote_message'].forEach(function (containerName) {
                // eslint-disable-next-line vars-on-top
                var container = self.getChild(containerName);

                if (typeof(container) === 'undefined')
                    return;

                container.elems().forEach(function (messageComponent) {
                    data.push(messageComponent.collectData());
                })
            });

            request = this.saveGiftData(data);

            if (initial !== true) {
                fullScreenLoader.startLoader();
                request.done(
                    function (response) {
                        messagesList.addSuccessMessage({ message: this.popUpForm.options.messages.gift });
                    }.bind(this)
                ).always(
                    function (response) {
                        self.getPopUp().closeModal();
                        fullScreenLoader.stopLoader(false);
                    }
                );
            }
        },

        /**
         * @param {*} data
         *
         * @returns {*}
         */
        saveGiftData: function (data) {
            var request,
                serviceUrl = resourceUrlManager.getUrlForGiftMessage(quote),
                payload = {
                    gift_message: data,
                    cartId: quote.getQuoteId()
                };

            if (request) {
                request.abort();
            }

            request = storage.post(
                serviceUrl,
                JSON.stringify(payload),
                false
            ).fail(function (response) {
                if (response.responseText) {
                    errorProcessor.process(response);
                }
            });

            return request;
        },

        /**
         * @returns {string}
         */
        getPopUp: function () {
            var self = this,
                buttons;

            if (popUp === null) {
                buttons = this.popUpForm.options.buttons;

                this.popUpForm.options.buttons = [{
                    class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                    text: buttons.save.text ? buttons.save.text : this.popUpForm.options.messages.update,
                    click: self.submit.bind(self)
                }, {
                    class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',
                    text: buttons.cancel.text ? buttons.cancel.text : this.popUpForm.options.messages.close,
                    click: function () {
                        this.closeModal();
                    }
                }];

                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            }

            return popUp;
        }
    });
});
