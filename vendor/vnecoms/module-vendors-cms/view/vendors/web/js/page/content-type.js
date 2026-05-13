
define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($) {
    'use strict';

    $.widget('mage.contenttype', {

        _create: function () {
            this._initEditAction();
        },

        _initEditAction: function () {

            var syncContentTypeTitle = function (event) {
                var value = $(event.target).val().trim(),
                    inputIdentifier = $('#contenttype_identifier');

                value = value.trim().toLowerCase().replace(/[^a-z0-9]+/g,'_');
                inputIdentifier.val(value);
            };

            this._on({
                // Sync content type title
                'change #contenttype_title': syncContentTypeTitle,
                'keyup #contenttype_title': syncContentTypeTitle,
                'paste #contenttype_title': syncContentTypeTitle,
            });
        },
    });
});
