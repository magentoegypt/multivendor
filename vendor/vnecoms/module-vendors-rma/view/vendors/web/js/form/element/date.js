define(
    [
        'Magento_Ui/js/form/element/date',
        'ko'
    ],
    function (
        Date,
        ko
    ) {
        return Date.extend({
            defaults: {
                template: 'Vnecoms_HelpDesk/form/element/date',
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

                return this;
            },
        });
    }
);