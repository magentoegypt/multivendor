/* global google */
/**
 * Google Autocomplete initializer
 */
define([
    'Magento_Ui/js/lib/view/utils/async',
    'uiRegistry',
    'ko',
    'underscore'
], function ($, registry, ko, _) {
    'use strict';

    return {
        isReady: ko.observable(false),
        options: {},
        restrictedCountryList: {},

        /**
         * @param {Object} autocomplete - Google Autocomplete object
         * @returns {void}
         */
        geolocate: function (autocomplete) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    var geolocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        },
                        circle = new google.maps.Circle({
                            center: geolocation,
                            radius: position.coords.accuracy
                        });

                    autocomplete.setBounds(circle.getBounds());
                });
            }
        },

        /**
         * @param {UIComponent} component
         * @returns {null|void}
         */
        registerField: function (component) {
            var self = this;

            if (this.isReady()) {
                return this.init(component);
            }

            this.isReady.subscribe(function (isReady) {
                if (isReady) {
                    return self.init(component);
                }

                return null;
            });

            return null;
        },

        /**
         * @param {UIComponent} component
         * @returns {void}
         */
        init: function (component) {
            var self = this;

            registry.get(component, function (rootComponent) {
                registry.get(component + '.street.0', function (inputComponent) {
                    $.async({
                        selector: '#' + inputComponent.uid
                    }, function (input) {
                        var autocomplete = new google.maps.places.Autocomplete(
                            input,
                            {
                                types: ['geocode'],
                                componentRestrictions: {country: self.restrictedCountryList}
                            }
                        );

                        autocomplete.setFields(['address_components', 'name']);

                        // eslint-disable-next-line max-nested-callbacks
                        autocomplete.addListener('place_changed', function () {
                            self.fillInAddress(autocomplete, rootComponent);
                        });

                        self.geolocate(autocomplete);
                    });
                });
            });
        },

        /**
         * @param {Object} autocomplete - Google Autocomplete object
         * @param {UIComponent} rootComponent
         * @returns {void}
         */
        fillInAddress: function (autocomplete, rootComponent) {
            var self = this,
                place = autocomplete.getPlace(),
                street = place.name.replace(',', ''),
                postcode = false,
                postcodeSuffix = false,
                isRegionApplied = false,
                streetComponent = rootComponent.getChild('street').getChild(0),
                stateSelect = rootComponent.getChild('region_id'),
                stateInput = rootComponent.getChild('region_id_input'),
                country,
                shortValue,
                longValue,
                addressType;

            if (!place.address_components) {
                return;
            }

            if (street && (streetComponent.value() === street)) {
                streetComponent.value.valueHasMutated();
            } else {
                streetComponent.value(street);
            }

            if (rootComponent.hasChild('postcode')) {
                rootComponent.getChild('postcode').value('');
            }

            if (rootComponent.hasChild('region_id_input')) {
                rootComponent.getChild('region_id_input').value('');
            }

            if (rootComponent.hasChild('city')) {
                rootComponent.getChild('city').value('');
            }

            if (rootComponent.hasChild('country_id')) {
                rootComponent.getChild('country_id').value('');
            }

            place.address_components.reverse();
            _.each(place.address_components, function (addressComponent) {
                addressType = addressComponent.types[0];
                shortValue = addressComponent.short_name;
                longValue = addressComponent.long_name;

                switch (addressType) {
                    case 'postal_code':
                        if (rootComponent.hasChild('postcode')) {
                            postcode = longValue;

                            if (postcodeSuffix) {
                                postcode = postcode + '-' + postcodeSuffix;
                            }

                            rootComponent.getChild('postcode').value(postcode);
                        }

                        break;
                    case 'postal_code_suffix':
                        postcodeSuffix = longValue;

                        break;
                    case 'country':
                        if (rootComponent.hasChild('country_id')) {
                            rootComponent.getChild('country_id').value(shortValue);
                            country = shortValue;
                        }

                        break;
                    case 'administrative_area_level_1':
                        country = rootComponent.getChild('country_id').value();

                        if (stateSelect && stateSelect.visible()) {
                            if (country === 'DE') {
                                self.changeRegions(self.options.regions, country);
                            }

                            if (shortValue in self.options.regions[country]) {
                                stateSelect.value(self.options.regions[country][shortValue]);
                                isRegionApplied = true;
                            }

                            break;
                        }

                        if (stateInput && stateInput.visible()) {
                            stateInput.value(longValue);
                        }

                        break;
                    case 'administrative_area_level_2':
                        if (stateSelect && stateSelect.visible() && !isRegionApplied) {
                            if (shortValue in self.options.regions[country]) {
                                stateSelect.value(self.options.regions[country][shortValue]);
                            }
                        }

                        break;
                    case 'locality':
                    case 'postal_town':
                        if (rootComponent.hasChild('city')) {
                            rootComponent.getChild('city').value(longValue);
                        }

                        break;
                    default: break;
                }
            });
        },

        /**
         * Changes regions for compatibility with Google Autocomplete API
         *
         * @param {Object} regions
         * @param {string} country
         * @returns {Object}
         */
        changeRegions: function (regions, country) {
            var googleGermanyStates = {
                BW: '80',
                BY: '81',
                BE: '82',
                BB: '83',
                HB: '84',
                HH: '85',
                HE: '86',
                MV: '87',
                NDS: '79',
                NRW: '88',
                RP: '89',
                SL: '90',
                SN: '91',
                SA: '92',
                SH: '93',
                TH: '94'
            };

            regions[country] = googleGermanyStates;

            return regions[country];
        }
    };
});
