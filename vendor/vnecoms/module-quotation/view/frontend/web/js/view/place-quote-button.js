/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
/**
 * Add to quote button in quote request
 */
define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        'ko',
        'Vnecoms_Quotation/js/action/place-quote',
        'Vnecoms_Quotation/js/model/email-form-usage-observe',
        'Vnecoms_Quotation/js/model/quote-model-selector',
        'Magento_Customer/js/model/customer'
    ],
    function (
        $,
        Component,
        ko,
        placeQuoteAction,
        emailFormUsageObserver,
        selector,
        customer
    ) {
        'use strict';

        /**
         * A view model for handling the add-to-quote button
         */
        return Component.extend({
            defaults: {
                template: 'Vnecoms_Quotation/quotepage/place-quote-button'
            },

            /**
             * Flag for to show the fields
             */
            showFields: emailFormUsageObserver.showFields,
            
            /**
             * Flag for allow to use form
             */
             allowToUseForm: emailFormUsageObserver.allowToUseForm(),

            /**
             * Flag to check if the quotation fields are ready for RFQ
             */
            quotationReady: ko.observable(false),

            /**
             * Flag for allowing to request a quote
             */
            readyToRequest: null,

            /**
             * Check if the customer is logged in
             */
            isCustomerLoggedIn: customer.isLoggedIn,

            /**
             * Show the login button
             */
            showLoginButton: null,

            /**
             * Show the request button
             */
            showRequestButton: null,

            /**
             * Init component
             */
            initialize: function () {
                this._super();
                var self = this;

                this.initLoginButton();
                this.initRequestButton();
            },

            /**
             * A function to request the quote.
             * If the billing address, shipping address and quotations fields are valid
             * then the quote will be requested.
             */
            validateQuote: function() {
                var requestAccount = false;

                if (selector.hasGuestCheckoutFields()) {
                    requestAccount = selector.getGuestCheckoutModel().requestAccount();
                } else if (this.allowToUseForm && !this.isCustomerLoggedIn()) {
                    requestAccount = true;
                }

                if (selector.hasQuotationFields()) {
                    /** Copy the names to the guest for to make the form valid */
                    emailFormUsageObserver.copyNames();

                    this.quotationReady(selector.getQuotationModel().validateFields());
                    if (!this.quotationReady()) {
                        this.scrollToError();
                    }
                }
            },


            /**
             * Scroll the page to the error
             * @return void
             */
            scrollToError: function(){
                var errorElement = $('._error').get(0);
                if (typeof errorElement != undefined) {
                    var offset = $(errorElement).offset();
                    if (typeof offset != 'undefined') {
                        $('html, body').animate({scrollTop: $(errorElement).offset().top}, 500);
                    }
                }
            },

            /**
             * Init the login button
             */
            initLoginButton: function () {
                var self = this;

                self.showLoginButton = ko.computed(function () {
                    return !self.isCustomerLoggedIn() && !self.allowToUseForm
                });
            },

            /**
             * Init the request button
             */
            initRequestButton: function () {
                var self = this;

                self.showRequestButton = ko.computed(function () {
                    return ((self.showFields() && self.allowToUseForm) || (self.isCustomerLoggedIn() && !self.allowToUseForm))
                });
            }
        });
    }
);
