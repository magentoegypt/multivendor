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
