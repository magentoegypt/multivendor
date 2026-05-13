/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'Vnecoms_Quotation/js/model/quote-model-selector'
    ],
    function(ko, $, Component, selector) {
        "use strict";

        /**
         * This is the model that handles the form behavior
         */
        return Component.extend({
            /**
             * Flag for the variables that are fired by the loaded shipping service.
             */
            loaded: false,

            /**
             * Flag for the guests fields need to be shown
             */
            showGuestField: ko.observable(true),

            /**
             * Flag for the non-guest fields need to be shown
             */
            showNonGuestField: ko.observable(false),

            /**
             * Flag for all the fields if they are allowed to be shown
             */
            showFields: ko.observable(false),

            /**
             * Init component
             */
            initialize: function () {
                var self = this;
                this._super();

                this.showGuestField.extend({ notify: 'always' });
                this.showNonGuestField.extend({ notify: 'always' });
                this.showFields.extend({ notify: 'always' });

                if (!this.allowToUseForm()) {
                    this.initAllowToUseForm();
                } else {
                        if (!this.loaded) {
                            this.initAllowToUseForm();
                            setTimeout(function(){
                                self.initCopyNameService();
                            }, 1500);
                            this.loaded = true;

                            if (selector.hasLoginModel()) {
                                selector.getLoginModel().emailHasChanged();
                            }

                            if (selector.hasGuestModel()) {
                                selector.getGuestCheckoutModel().requestAccount.subscribe(
                                    function(){self.updateFields()}
                                );
                            }
                        }
                }
            },

            /**
             * Init the allow to use form.
             */
            initAllowToUseForm: function () {
                var self = this;

                /** Init fields */
                self.updateFields();

                if (selector.hasLoginModel()) {
                    /** Subscribe to email change */
                    selector.getLoginModel().email.subscribe(function () {
                        self.updateFields()
                    });

                    /** Subscribe to password field */
                    selector.getLoginModel().isPasswordVisible.subscribe(function () {
                        self.updateFields()
                    });
                }
            },

            /**
             * Update the showGuestField, showNonGuestField and showFields
             * variable by the validForShow and requestAccount
             */
            updateFields: function () {
                var validForShow = true, requestAccount = true, configAllowForm = this.allowToUseForm();

                if (selector.hasLoginModel()) {
                    validForShow = !selector.getLoginModel().isPasswordVisible() && this.isLoginValid();
                }

                if (this.allowToUseGuest()) {
                    if (selector.hasGuestModel()) {
                        requestAccount = selector.getGuestModel().requestAccount();
                    }
                }

                /*this.showGuestField(validForShow && !requestAccount && configAllowForm);*/
                this.showNonGuestField(validForShow && requestAccount && configAllowForm);
                this.showFields(validForShow && configAllowForm);
            },

            /**
             * Checks if the email is valid
             *
             * @returns {boolean}
             */
            isLoginValid: function () {
                if (selector.getLoginFormField().first().val()) {
                    selector.getLoginForm().validation();
                    return Boolean(selector.getLoginFormField().valid());
                }

                return false;
            },

            /**
             * Update the firstname and lastname in the quotation form each time requestAccount is changed.
             */
            initCopyNameService: function () {
                if (selector.hasGuestModel()) {
                    selector.getGuestModel().requestAccount.subscribe(function () {
                        this.copyNames();
                    }, this);
                }
            },

            /**
             * Copy the first name and last name to the quotation fields.
             */
            copyNames: function () {
                this.updateQuotationField('firstname');
                this.updateQuotationField('lastname');
            },

            /**
             * Update a quotation field
             * KO will be notified by the change function on the input.
             *
             * @param name
             */
            updateQuotationField: function (name) {
                var field = this.getField(name, 'quotationFieldData');
                field.change();
            },


            /**
             * Get a field
             *
             * @param fieldName
             * @param type
             * @returns {*|jQuery|HTMLElement}
             */
            getField: function (fieldName, type) {
                return $('[name="'+type+'.'+fieldName+'"] input');
            },

            /**
             * Get allow to use guest mode
             * @returns boolean
             */
            allowToUseGuest: function () {
                return window.checkoutConfig.isGuestCheckoutAllowed;
            },

            /**
             * Get allow to use form
             * @returns boolean
             */
            allowToUseForm: function () {
                return true;
            }
        })();
    }
);
