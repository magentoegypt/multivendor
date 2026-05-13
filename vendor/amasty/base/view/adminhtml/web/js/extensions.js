/**
 *  Amasty Base Extensions UI Component
 */

define([
    'uiComponent'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Amasty_Base/extensions/extensions',
            templates: {
                updateButtons: 'Amasty_Base/extensions/update-buttons',
                filterButtons: 'Amasty_Base/extensions/filter-buttons',
                table: 'Amasty_Base/extensions/table'
            },
            modulesData: [],
            update: [],
            solutions: [],
            stateValues: {
                default: 'default',
                solutions: 'solutions',
                update: 'update'
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();

            this.update = this.prepareModules(this.modulesData.filter(function (item) {
                return item.has_update;
            }));

            this.solutions = this.prepareModules(this.modulesData.filter(function (item) {
                return item.upgrade_url;
            }));

            this.modules(this.prepareModules(this.modulesData));

            return this;
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            return this._super()
                .observe({
                    state: 'default',
                    modules: []
                });
        },

        /**
         * Use Extensions Filter
         *
         * @param {String} state
         * @returns {void}
         */
        useGridFilter: function (state) {
            this.state(state);

            if (this.stateValues.default === state) {
                this.modules(this.prepareModules(this.modulesData));

                return;
            }

            this.modules(this[state]);
        },

        /**
         * Is filter active
         *
         * @param {String} state
         * @returns {Boolean}
         */
        isActive: function (state) {
            return this.state() === state;
        },

        /**
         * Prepare modules data
         *
         * @param {Array} data
         * @returns {Array}
         */
        prepareModules: function (data) {
            var availableUpgrade = data.filter(function (item) {
                    return item.upgrade_url;
                }),
                needUpdate = data.filter(function (item) {
                    return item.has_update && !item.upgrade_url;
                }),
                modules = data.filter(function (item) {
                    return !item.has_update && !item.upgrade_url;
                });

            return availableUpgrade.concat(needUpdate, modules);
        }
    });
});
