/**
 * Copyright © 2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true, jquery:true*/
define([
	'jquery',
	'jquery/ui',
	'jquery/intltellinput',
	'jquery/mask',
	'Vnecoms_Sms/js/utils'
], function($){
	"use strict";

	$.widget('vnecoms.smsLogin', {
		options: {
			login_type_config: '1',
			mobileInput: '#mobile-number-input',
			mobileField: '#mobile-number',
			loginTypeField: '#login_type',
			initialCountry: '',
			geoIpUrl: 'https://ipinfo.io',
			allowDropdown: '',
			onlyCountries: [],
			preferredCountries: [],
			fieldCtrSelector: '.sms-login-field-ctrl',
			fieldSelector: '.sms-login-field',
			passwordField: '#magento-password-login',
			actionToobarLogin: '.actions-toolbar-login',
			actionToobarOtp: '.actions-toolbar-otp'
		},

		_create: function() {
			this._initMobileInput();
			this._bindEvents();
		},

		/**
		 * Bind Events
		 */
		_bindEvents: function(){
			var self = this;
			$(this.options.fieldCtrSelector).click(function(event){
				event.preventDefault();
				$(self.options.fieldCtrSelector+'.selected').removeClass('selected');
				$(self.options.fieldSelector).hide();
				$($(this).attr('href')).show();
				$(this).addClass('selected');
				$(self.options.loginTypeField).val($(this).attr('href').replace('#',''));

				if ($(self.options.loginTypeField).val() == "by_mobile"
					&& self.options.login_type_config == 2
				) {
					$(self.options.passwordField).hide();
					$(self.options.actionToobarLogin).hide();
					$(self.options.actionToobarOtp).show();
				} else {
					$(self.options.passwordField).show();
					$(self.options.actionToobarLogin).show();
					$(self.options.actionToobarOtp).hide();
				}


			});
		},

		/**
		 * Init mobile input
		 */
		_initMobileInput: function(){
			var self = this;
			var data= {
				initialCountry: this.options.initialCountry,
				allowDropdown: this.options.allowDropdown,
				onlyCountries: this.options.onlyCountries,
				preferredCountries:this.options.preferredCountries
			}
			if(this.options.initialCountry == 'auto'){
				data['geoIpLookup'] = function(callback) {
					$.get(self.options.geoIpUrl, function() {}, "jsonp").always(function(resp) {
						var countryCode = (resp && resp.country) ? resp.country : "";
						callback(countryCode);
					});
				};
			}

			$(this.options.mobileInput).intlTelInput(data).done(function() {
				self._initMask();
				self._updateMobileNumber();
				$(self.options.mobileInput).on('keyup', function() {
					self._updateMobileNumber();
				}).on("countrychange", function(e, countryData) {
					self._initMask();
					self._updateMobileNumber();
				});
			});
		},
		_initMask: function(){
			var countryData = $(this.options.mobileInput).intlTelInput("getSelectedCountryData");
			if(!countryData.iso2) return false;
			var numberType = intlTelInputUtils.numberType['MOBILE'];
			var mask = intlTelInputUtils.getExampleNumber(countryData.iso2, true, numberType);
			$(this.options.mobileInput).mask(mask.replace(/([0-9])/g, '0'));
		},
		/**
		 * Update mobile number
		 */
		_updateMobileNumber: function(){
			$(this.options.mobileField).val($(this.options.mobileInput).intlTelInput("getNumber"));
		}
	});

	return $.vnecoms.smsLogin;
});
