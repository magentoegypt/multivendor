/**
 * Hub Market — the first step is called "Shipping Info".
 *
 * Core registers it as "Shipping". Retranslating that string would also rename
 * the Shipping row in the order summary and the account address book, so the
 * change is made where the name actually lives: on the step the navigator holds.
 *
 * Mixed into the shipping view rather than the progress bar because that is what
 * registers the step — doing it in the bar's template would leave the navigator
 * and the bar disagreeing about what the step is called.
 */
define([
    'Magento_Checkout/js/model/step-navigator',
    'mage/translate'
], function (stepNavigator, $t) {
    'use strict';

    return function (Component) {
        return Component.extend({
            /** @inheritdoc */
            initialize: function () {
                var renamed = false;

                this._super();

                stepNavigator.steps().forEach(function (step) {
                    if (step.code === 'shipping') {
                        step.title = $t('Shipping Info');
                        renamed = true;
                    }
                });

                //  The titles are plain strings on plain objects, so the array
                //  has to be told it changed for the bar to re-read them.
                if (renamed) {
                    stepNavigator.steps.valueHasMutated();
                }

                return this;
            }
        });
    };
});
