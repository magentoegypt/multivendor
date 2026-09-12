/**
 * Gives the RMA attachment uploader a usable form key.
 *
 * CL036-DEV01.30 item 3: submitting a return request showed BOTH
 * "مفتاح النموذج غير صالح" (invalid form key) AND the success message. The
 * access log tells the story - `POST /ar/vrma/customer/upload/ -> 302` at
 * 09:49:37, then `POST /ar/vrma/customer/save/ -> 302` four seconds later. The
 * SAVE was fine; the ATTACHMENT UPLOAD was rejected by Magento's CSRF validator
 * and queued the error, which then rendered beside the save's success on the
 * list page.
 *
 * Why it was rejected: Magento_Ui's file-uploader builds its default config as
 *
 *     uploaderConfig: { formData: { 'form_key': window.FORM_KEY } }
 *
 * and that object literal is evaluated when the MODULE IS DEFINED. `FORM_KEY` is
 * an admin global - it is never defined on the storefront (verified: the phrase
 * does not appear in a rendered page, and `typeof window.FORM_KEY` is
 * "undefined" on the RMA form) - so the jQuery-fileupload path posts
 * `form_key=undefined` every time. The newer uppy path in the same file reads
 * `window.FORM_KEY !== undefined ? window.FORM_KEY : $.cookie('form_key')`,
 * which is why the reply form's upload succeeds while this one does not.
 *
 * So: take the key the same way that working path does. The cookie is present
 * on these pages (checked live), and patching in `initialize` lands before
 * `initUploader()` reads `uploaderConfig`.
 *
 * Scoped to the RMA uploader rather than fixing Magento_Ui globally, because
 * every other uploader on this storefront either uses the uppy path or is not
 * reached by a customer.
 */
define(['jquery', 'mage/cookies'], function ($) {
    'use strict';

    return function (Uploader) {
        return Uploader.extend({
            /** @inheritdoc */
            initialize: function () {
                var formKey;

                this._super();

                formKey = window.FORM_KEY !== undefined ? window.FORM_KEY : $.mage.cookies.get('form_key');

                if (formKey) {
                    this.uploaderConfig = $.extend(true, {}, this.uploaderConfig, {
                        formData: {
                            'form_key': formKey
                        }
                    });
                }

                return this;
            }
        });
    };
});
