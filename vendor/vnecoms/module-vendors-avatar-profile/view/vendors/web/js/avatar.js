define(
    [
        "jquery",
        'Magento_Ui/js/lib/validation/validator',
        'Magento_Ui/js/form/element/file-uploader',
        'ko'
    ],
    function (
        $,
        validator,
        UpLoader,
        ko
    ) {
        return UpLoader.extend({
            defaults: {
                isMultipleFiles : false,
                template: 'Vnecoms_VendorsAvatarProfile/uploader',
                previewTmpl: 'Vnecoms_VendorsAvatarProfile/preview',
                vendor_name : false,
                avatar : false
            },

            /**
             * Invokes initialize method of parent class,
             * contains initialization logic
             */
            initialize: function () {
                _.bindAll(this, 'reset');
                this._super()
                    .setInitialValue()
                    ._setClasses()
                    .initSwitcher();
                this.loadUserProfile();
                return this;
            },

            /**
             * Replace Input type File with Span
             * and bind click event
             */
            replaceInputTypeFile: function (fileInput) {

                let fileId = fileInput.id, fileName = fileInput.name,
                    spanElement = '<span id=\'' + fileId + '\'></span>';

                $('#' + fileId).closest('.file-uploader-area').attr('upload-area-id', fileName);
                $(fileInput).replaceWith(spanElement);
                $('#' + fileId).closest('.file-uploader-area').find('.file-uploader-button:first').on('click', function () {
                    $('#' + fileId).closest('.file-uploader-area').find('.uppy-Dashboard-browse').trigger('click');
                });

                $('.file-uploader-preview-box').on('click', function () {
                    $('#' + fileId).closest('.file-uploader-area').find('.uppy-Dashboard-browse').trigger('click');
                });

            },

            /**
             * Retrieve data to authorized user.
             *
             * @return array
             */
            loadUserProfile: function () {
                var self = this;
                $.ajax({
                    type: 'GET',
                    url: this.profileUrl,
                    showLoader: false,
                    dataType: 'json',
                    context: this,

                    /**
                     * @param {Object} response
                     * @returns void
                     */
                    success: function (response) {
                        if (response.error == undefined) {
                            self.addFile(response);
                        }
                    },

                    /**
                     * @param {Object} response
                     * @returns {String}
                     */
                    error: function (response) {
                        return response.message;
                    }
                });
            },

            getVendorName : function () {
                return this.vendor_name;
            },
        });
    }
);
