/**
 * Quote fields manager
 */
define(
    [
        'jquery',
        'ko',
        'Magento_Ui/js/form/form',
        'Vnecoms_Quotation/js/model/email-form-usage-observe',
        'mage/translate',
        'uiRegistry',
        'Magento_Customer/js/model/customer'
    ],
    function (
        $,
        ko,
        Component,
        emailFormUsageObserver,
        $t,
        registry,
        customer
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Vnecoms_Quotation/quotepage/fields'
            },

            formSelector: '#quotation-fields',
            allowToUseForm: emailFormUsageObserver.showFields,
            showGuestField: emailFormUsageObserver.showGuestField,
            isCustomerLoggedIn: customer.isLoggedIn,
            loginEnabled: emailFormUsageObserver.allowToUseForm(),

            showQuotationFields: null,

            initialize: function () {
                this._super();

                this.initShowQuotationButton();

                this.allowToUseForm.extend({ notify: 'always' });

                emailFormUsageObserver.updateFields();
            },

            /**
             * Validate the fields
             * @return boolean
             */
            validateFields: function () {
                var emailValidationResult = false,
                    loginFormSelector = 'form[data-role=email-with-possible-login]',
                    firstNameSelector = '[name="quotationFieldData.firstname"]',
                    lastNameSelector = '[name="quotationFieldData.lastname"]';

                this.source.set('params.invalid', false);

                if (customer.isLoggedIn()) {
                    emailValidationResult = true;
                    this.triggerValidateFieldSet('quotationFieldData');
                } else {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }

                if (!this.showGuestField() || customer.isLoggedIn()) {
                    $(firstNameSelector).removeClass('_required');
                    $(lastNameSelector).removeClass('_required');
                } else {
                    $(firstNameSelector).addClass('_required');
                    $(lastNameSelector).addClass('_required');
                    this.triggerValidateFieldSet('guestFieldData');
                }

                this.triggerValidateFieldSet('custom_attributes');

                return !(this.source.get('params.invalid')) && emailValidationResult;
            },

            /**
             * Trigger field validation for a fieldset
             *
             * @param fieldSet
             */
            triggerValidateFieldSet: function (fieldSet) {
                this.source.trigger(fieldSet+'.data.validate');
                if (typeof this.source.get('.'+fieldSet) !== 'undefined') {
                    this.source.trigger('.'+fieldSet+'.data.validate');
                }
            },

            /**
             * Init the login button
             */
            initShowQuotationButton: function () {
                var self = this;

                self.showQuotationFields = ko.computed(function () {
                    return self.allowToUseForm() || (self.isCustomerLoggedIn() && !self.allowToUseForm())
                });
            }
        });
    }
);
