define([
    'Magento_Ui/js/form/element/single-checkbox'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            templates: {
                checkbox: 'Amasty_CheckoutGiftWrap/form/gift_messages/checkbox'
            }
        },

        /**
         *
         * @param component
         * @param event
         *
         * @returns {void}
         */
        showPopupHandler: function (component, event) {
            event.stopPropagation();

            this.trigger('edit_link_click');
        }
    });
});
