/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/place-order',
        'Vnecoms_Quotation/js/action/redirect-on-success',
        'Vnecoms_Quotation/js/model/resource-url-manager',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/model/customer',
        'mage/url'
    ],
    function (
        $,
        quote,
        placeOrderService,
        redirectOnSuccessAction,
        resourceUrlManagerModel,
        $t,
        globalMessageList,
        fullScreenLoader,
        customer,
        url
    ) {
        'use strict';

        /**
         * This action handles the quotation placement (saves the quote)
         */
        return function (){

            /**
             * Handle undefined result
             *
             * @return void
             */
            function handleUndefined() {
                globalMessageList.addErrorMessage({
                    message: $t('Something went wrong while processing your quote. Please try again later.')
                });

                stopLoader();
                scrollToTop();
            }

            /**
             * Handle when success
             *
             * @return void
             */
            function handleSuccess(quoteId){
                redirectOnSuccessAction.execute(quoteId);
            }

            /**
             * Handle when error
             *
             * @return void
             * @param result
             */
            function handleError(result){
                if (result.status == 401) {
                    window.location.replace(url.build('customer/account/login/'));
                } else {
                    if (result.message != 'undefined') {
                        $.each(result.message.split('/n'), function (index, errorMessage) {
                            globalMessageList.addErrorMessage({message: errorMessage});
                        });
                    }

                    stopLoader();
                    scrollToTop();
                }
            }

            /**
             * Two times stop loader because placeOrderService creates an extra loader.
             *
             * @return void
             */
            function stopLoader(){
                fullScreenLoader.stopLoader(true);
                fullScreenLoader.stopLoader(true);
            }

            /**
             * Scroll the page to the error
             * @reutn void
             */
            function scrollToTop(){
                $('html, body').animate({scrollTop: $("#checkout").offset().top}, 500);
            }

            /**
             * Get an object of quote data
             *
             * @return object
             */
            function getQuoteData(){
                return {
                    quoteId: quote.getQuoteId(),
                    form_key: window.checkoutConfig.formKey,
                    customer_email: quote.guestEmail == null ? customer.customerData.email : quote.guestEmail,
                };
            }

            /**
             * Get the request URL
             *
             * @returns {*|string}
             */
            function getRequestUrl(){
                return resourceUrlManagerModel.getUrlForPlaceQuote(quote, getQuoteData());
            }

            /**
             * Place the quote
             *
             * @see Quotation/Controller/Quote/Ajax/CreateQuote.php
             */
            return placeOrderService(getRequestUrl(), getQuoteData(), globalMessageList).success(
                function(result) {
                    if (result.error) {
                        handleError(result);
                    } else if(result.success) {
                        handleSuccess(result.last_quote_id);
                    } else {
                        handleUndefined();
                    }
                }
            ).fail(
                function(response) {
                    if (response.status == 404 || response.status == 403) {
                        location.reload();
                    }
                }
            );
        };
    }
);
