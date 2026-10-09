/**
 * Hub Market sign-in form: e-mail + password and the verification code are two
 * separate flows (CL036-DEV16, 14zb93nwtd5).
 *
 * Replaces Vnecoms_Sms/js/validate-customer.js on the theme's login forms. That
 * script sent EVERY submit to vsms/login/loginPost and opened the
 * "2-Step Verification" dialog on any success, so an e-mail + password login
 * also had to send an OTP to the account's phone and wait for it. The client
 * asked for the two to be separate:
 *
 *  - E-mail tab: the form posts `hm_password_login=1`; the server checks the
 *    password and logs the customer in (MagentoEgypt\SmsExtend\Controller\Login
 *    \LoginPost::loginByPassword). No OTP is sent and no dialog opens. The page
 *    reloads, as it does after a confirmed code, and the login page sends the
 *    customer on.
 *  - Verification code tab: the server resolves the account from the mobile
 *    number, then this script sends the code straight away and opens the dialog
 *    on the step that takes it. The stock dialog first showed an extra
 *    "Get OTP" step, meant for the 2FA case that no longer happens here.
 *
 * Verify, resend, the countdown and the server's limits are unchanged.
 *
 * The visible strings come in as options, translated in the template with __():
 * a new JS file is not in the deployed js-translation.json, so $t() here would
 * stay English on the Arabic store.
 *
 * The widget keeps the stock name (mage.customerLoginValidate), so any code that
 * looks it up by that name still finds it.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/validation/validation'
], function ($, modal, alert) {
    'use strict';

    $.widget('mage.customerLoginValidate', {
        options: {
            loginButtonSelector: '#login-form .action.login',
            loadingBoxSelector: '#ves-loadingbox',
            otpDialogSelector: '#sms-otp-dialog',
            loginTypeSelector: '[name="login_type"]',
            emailLoginType: 'by_email',
            sendOtpURL: '',
            verifyOtpURL: '',
            login_type_config: '1',
            dialogTitle: 'Verification code',
            verifyErrorTitle: 'Verify Error',
            requiredMessage: 'This is a required field.',
            resendAfterText: 'Resend after %1 seconds',
            resendText: 'Resend OTP'
        },
        mobileNumber: '',
        secureKey: '',

        /** @private */
        _create: function () {
            this.initValidation();
            this.initOtpForm();
        },

        /**
         * The code dialog: verify, resend and the countdown, as stock.
         */
        initOtpForm: function () {
            modal({
                type: 'popup',
                modalClass: 'otp-verify-modal',
                responsive: true,
                innerScroll: true,
                title: this.options.dialogTitle,
                buttons: []
            }, $(this.options.otpDialogSelector));

            $('#send-otp-btn').on('click', function () {
                this.sendOtp(0);
            }.bind(this));

            $('#resend-otp-btn').on('click', function () {
                if (!$('#resend-otp-btn').hasClass('running')) {
                    this.sendOtp(1);
                }

                return false;
            }.bind(this));

            $('#verify-otp-btn').on('click', function () {
                this.verifyOtp();
            }.bind(this));
        },

        /**
         * @returns {Boolean} true when the e-mail tab is the one submitted
         */
        isPasswordLogin: function () {
            var type = this.element.find(this.options.loginTypeSelector).val();

            return !type || type === this.options.emailLoginType;
        },

        /**
         * @returns {String}
         */
        formKey: function () {
            return this.element.find('input[name="form_key"]').val() || '';
        },

        /**
         * Submit through vsms/login/loginPost, as stock, marked as a password
         * login when it comes from the e-mail tab.
         */
        initValidation: function () {
            var self = this;

            this.element.validation({
                /**
                 * @param {HTMLFormElement} form
                 * @returns {Boolean}
                 */
                submitHandler: function (form) {
                    var formData = new FormData($(form)[0]),
                        passwordLogin = self.isPasswordLogin();

                    if (passwordLogin) {
                        formData.append('hm_password_login', '1');
                    }

                    $(self.options.loadingBoxSelector).addClass('show');
                    $.ajax({
                        url: $(form).data('test_login_action'),
                        data: formData,
                        type: 'post',
                        dataType: 'json',
                        cache: false,
                        contentType: false,
                        processData: false,

                        /** @inheritdoc */
                        success: function (res) {
                            if (res.success && res.logged_in) {
                                //  Signed in. Keep the loader up through the
                                //  reload so the form cannot be sent twice.
                                window.location.reload();

                                return;
                            }

                            $(self.options.loadingBoxSelector).removeClass('show');

                            if (!res.success) {
                                //  The server queued the error (wrong password,
                                //  unknown number, locked account) as a message;
                                //  customer-data reloads the messages section
                                //  after this POST and shows it, as stock.
                                return;
                            }

                            self.mobileNumber = res.mobilenumber;
                            self.secureKey = res.secure_key;
                            $('#sms-otp-dialog-mobile').text(res.mobilenumber);

                            if (passwordLogin) {
                                //  The server answered with the 2FA handshake
                                //  (an older server). Keep the stock dialog.
                                self.openOtpForm();
                            } else {
                                self.sendOtp(0, true);
                            }
                        },

                        /** @inheritdoc */
                        error: function () {
                            $(self.options.loadingBoxSelector).removeClass('show');
                        }
                    });

                    return false;
                }
            });
            $(this.options.loginButtonSelector).attr('disabled', false);
        },

        /**
         * Send (or resend) the code.
         *
         * @param {Number} isResend
         * @param {Boolean} [openDialog] open the dialog on the code step once sent
         */
        sendOtp: function (isResend, openDialog) {
            var self = this;

            $('#send-otp-btn').prop('disabled', true);

            if (openDialog) {
                $(this.options.loadingBoxSelector).addClass('show');
            }

            $.ajax({
                url: this.options.sendOtpURL,
                method: 'POST',
                data: {
                    mobile: this.mobileNumber,
                    'secure_key': this.secureKey,
                    resend: isResend,
                    'form_key': this.formKey()
                },
                dataType: 'json'
            }).done(function (response) {
                $('#send-otp-btn').prop('disabled', false);
                $(self.options.loadingBoxSelector).removeClass('show');

                if (response.success) {
                    self.openVerifyOtpForm();

                    if (openDialog) {
                        $(self.options.otpDialogSelector).modal('openModal');
                    }
                    self.runCountDown();
                } else {
                    alert({
                        modalClass: 'confirm ves-error',
                        title: self.options.verifyErrorTitle,
                        content: response.msg
                    });
                }
            }).fail(function () {
                $('#send-otp-btn').prop('disabled', false);
                $(self.options.loadingBoxSelector).removeClass('show');
            });
        },

        /**
         * Resend countdown, as stock.
         */
        runCountDown: function () {
            var resendBtn = $('#resend-otp-btn'),
                count;

            if (!resendBtn.hasClass('running')) {
                resendBtn.addClass('running');
            }

            if (!resendBtn.data('couting')) {
                resendBtn.data('couting', resendBtn.data('time'));
            }
            count = parseInt(resendBtn.data('couting'), 10) - 1;
            resendBtn.data('couting', count);
            resendBtn.text(this.options.resendAfterText.replace('%1', count));

            if (count <= 0) {
                resendBtn.removeClass('running');
                resendBtn.text(this.options.resendText);

                return;
            }
            setTimeout(this.runCountDown.bind(this), 1000);
        },

        /**
         * Verify the code; the server logs the customer in on a match.
         */
        verifyOtp: function () {
            var input = $('#sms-otp-input'),
                otp = input.val();

            $('#sms-otp-error').remove();

            if ($('#verify-otp-btn').hasClass('verifying')) {
                return;
            }

            if (!otp) {
                input.after($('<div id="sms-otp-error" class="sms-otp-error" for="sms-otp-input"></div>')
                    .text(this.options.requiredMessage));

                return;
            }

            $('#verify-otp-btn').addClass('verifying');

            $.ajax({
                url: this.options.verifyOtpURL,
                method: 'POST',
                data: {
                    mobile: this.mobileNumber,
                    'secure_key': this.secureKey,
                    otp: otp,
                    'form_key': this.formKey()
                },
                dataType: 'json'
            }).done(function (response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    $('#verify-otp-btn').removeClass('verifying');
                    input.val('');
                    input.after($('<div id="sms-otp-error" class="sms-otp-error" for="sms-otp-input"></div>')
                        .text(response.msg));
                }
            }).fail(function () {
                $('#verify-otp-btn').removeClass('verifying');
            });
        },

        /**
         * The stock first step ("Get OTP"), only for the 2FA fallback.
         */
        openOtpForm: function () {
            $('.sms-otp-step-1').show();
            $('.sms-otp-step-2').hide();
            $(this.options.otpDialogSelector).modal('openModal');
        },

        /**
         * The step that takes the code.
         */
        openVerifyOtpForm: function () {
            $('.sms-otp-step-1').hide();
            $('.sms-otp-step-2').show();
            $('#mobile-number-id').val(this.mobileNumber);
        }
    });

    return $.mage.customerLoginValidate;
});
