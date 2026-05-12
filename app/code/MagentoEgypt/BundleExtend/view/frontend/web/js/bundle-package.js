define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/url',
    './swatch-renderer',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function (Component, ko, $, urlBuilder, SwatchRenderer, customerData, priceUtils, modal, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            bundleJson: {}
        },

        initialize: function () {
            this._super();

            this.items = ko.observableArray([]);
            this.itemsCount = ko.computed(() => this.items().length);

            this.swapSelections = ko.observableArray([]);
            this.currentSelection = ko.observable(null);
            this.currentOptionId = null;
            this.currentOptionTitle = ko.observable('');
            this.isExpanded = ko.observable(true);
            this.disableAddToCart = ko.observable(false);

            this.deliveryFee = ko.observable(5); // configurable

            this.regularPrice = ko.computed(() => {
                return this.items().reduce((sum, item) => {
                    return sum + 1*item.regularPrice;
                }, 0);
            });

            this.subtotal = ko.computed(() => {
                return this.items().reduce((sum, item) => {
                    return sum + 1*item.price;
                }, 0);
            });

            this.savings = ko.computed(() => {
                return this.regularPrice() - this.subtotal();
            });

            this.total = ko.computed(() => {
                return this.subtotal() + this.deliveryFee();
            });

            this.formatted = {
                regular: ko.computed(() =>
                    priceUtils.formatPrice(this.regularPrice(), this.bundleJson.priceFormat)
                ),
                subtotal: ko.computed(() =>
                    priceUtils.formatPrice(this.subtotal(), this.bundleJson.priceFormat)
                ),
                savings: ko.computed(() =>
                    priceUtils.formatPrice(this.savings(), this.bundleJson.priceFormat)
                ),
                delivery: ko.computed(() =>
                    priceUtils.formatPrice(this.deliveryFee(), this.bundleJson.priceFormat)
                ),
                total: ko.computed(() =>
                    priceUtils.formatPrice(this.total(), this.bundleJson.priceFormat)
                )
            };

            this.collectSelectedItems();
            this.initModal();

            return this;
        },

        toggleExpand: function() {
            this.isExpanded(!this.isExpanded());
        },

        addAllToCart: function () {
            $("body").trigger('processStart');
            $('#product_addtocart_form .super-attribute-select').remove();
            $('.super-attribute-select').each(function(){
                var pid = $(this).closest('.hd-configurable-container').data('product-id');
                var name = $(this).attr('name').replace('super_attribute', 'super_attribute[' + pid + ']');
                $('#product_addtocart_form').append($(this).clone().attr('name', name));
            });
            setTimeout(function() {
                $('#product-addtocart-button').trigger('click');
            }, 40);
            var stopLoader = setInterval(function(){
                if($(document).find('.flycart-animated-add').length){
                    $("body").trigger('processStop');
                    clearInterval(stopLoader);
                }
            },100);
        },

        openProduct: function(item) {
            window.open(item.productUrl, '_blank').focus();
        },

        addSingleToCart: function (item) {
            var self = this;

            var payload = {
                product: item.productId,
                qty: 1,
                form_key: $('input[name="form_key"]').val(),
                ajax: 1
            };

            $.ajax({
                url: item.cartUrl,
                type: 'POST',
                data: payload,
                dataType: 'json',
                cache: false,
                showLoader: true,
                success: function(response, status) {
					if (status == 'success') {
                        if (response.ui) {
                            if(response.animationType == 'popup') {
                                if($(document).find('.ajaxCartForm').length){
									$(document).find('.ajaxCartForm .modal-header .action-close').trigger('click');
								}

								$('body').append('<div id="popup_ajaxcart_success" class="popup__main popup--result"></div>');

								var options =
								{
									type: 'popup',
									modalClass: "success-ajax--popup viewBox",
									responsive: true,
									innerScroll: true,
									title: false,
									buttons: false
								};
								var popup = modal(options, $('#popup_ajaxcart_success'));
								$('#popup_ajaxcart_success').html(response.ui + response.related);
								$('#popup_ajaxcart_success').trigger('contentUpdated');
								$('#popup_ajaxcart_success').modal('openModal').on('modalclosed', function() {
									$('#popup_ajaxcart_success').parents('.success-ajax--popup').remove();
								});
                            } else if(response.animationType == 'flycart'){
								var $animatedObject = jQuery('<div class="flycart-animated-add" style="position: absolute;z-index: 99999;">'+response.image+'</div>');

								
								$animatedObject.css({top: 1, left: 1});
								jQuery('html').append($animatedObject);

								jQuery('#footer-cart-trigger').addClass('active');
								jQuery('#footer-mini-cart').slideDown(300);

								var gotoX = jQuery("#fixed-cart-footer").offset().left + 20;
								var gotoY = jQuery("#fixed-cart-footer").offset().top;

								if($(document).find('.ajaxCartForm').length){
									$(document).find('.ajaxCartForm .modal-header .action-close').trigger('click');
								}

								$animatedObject.animate({
									opacity: 0.6,
									left: gotoX,
									top: gotoY
								}, 2000,
								function () {
									$animatedObject.fadeOut('fast', function () {
										$animatedObject.remove();
										jQuery('html').removeClass('add-item-success');
									});
								});
                            } else {
								$(document).find('.quickViewDetails .modal-header .action-close').trigger('click');
								$('[data-block="minicart"]').find('[data-role="dropdownDialog"]').dropdownDialog("open");
								setTimeout(function(){
									$("header.page-header").removeClass("show-sticky-menu");
									$('[data-block="minicart"]').find('[data-role="dropdownDialog"]').dropdownDialog("close");
								},5000);
							}
                        }
                    }
                }
            });
        },

        initModal: function () {
            this.modal = modal({
                type: 'slide',
                title: $t('Make New Selection'),
                buttons: [],
                modalClass: 'hd-swap-slide-modal'
            }, document.getElementById('hd-swap-modal'));
        },

        collectSelectedItems: function () {
            var json = this.bundleJson,
                result = [],
                priceFormat = json.priceFormat;

            Object.keys(json.selected).forEach(optionId => {
                var option = json.options[optionId];

                json.selected[optionId].forEach(selectionId => {
                    var s = option.selections[selectionId];

                    result.push({
                        optionId: optionId,
                        selectionId: selectionId,
                        cartUrl: s.cartUrl,
                        productId: s.productId,
                        optionTitle: option.title,
                        name: s.name,
                        sku: s.sku,
                        productUrl: s.productUrl,
                        type: s.type,
                        configurableConfig: s.configurableConfig,
                        swatchConfig: s.swatchConfig,
                        sizeConfig: s.sizeConfig,
                        image: s.image,
                        price: s.prices.discountedPrice.amount,
                        regularPrice: s.prices.oldPrice.amount || s.prices.finalPrice.amount,
                        isSp: 1*s.prices.finalPrice.amount < 1*s.prices.oldPrice.amount,
                        formattedPrice: priceUtils.formatPrice(
                            s.prices.finalPrice.amount,
                            priceFormat
                        ),
                        formattedBasePrice: priceUtils.formatPrice(
                            s.prices.oldPrice.amount,
                            priceFormat
                        )
                    });
                });
            });

            this.items(result);
        },

        openSwap: function (optionId) {
            var option = this.bundleJson.options[optionId],
                selectedId = this.bundleJson.selected[optionId][0],
                priceFormat = this.bundleJson.priceFormat,
                list = [];

            this.currentOptionId = optionId;
            this.currentOptionTitle(option.title);

            var selected = option.selections[selectedId];

            Object.keys(option.selections).forEach(id => {
                if (id !== selectedId) {
                    var s = option.selections[id];
                    var price = 1*s.prices.finalPrice.amount;
                    var currentPrice = 1*selected.prices.finalPrice.amount;
                    list.push({
                        selectionId: id,
                        name: s.name,
                        image: s.image,
                        sku: s.sku,
                        productUrl: s.productUrl,
                        type: s.type,
                        configurableConfig: s.configurableConfig,
                        swatchConfig: s.swatchConfig,
                        sizeConfig: s.sizeConfig,
                        optionTitle: option.title,
                        priceDiff: priceUtils.formatPrice(
                            price - currentPrice,
                            priceFormat
                        ),
                        isSamePrice: price === currentPrice,
                        signPlus: price > currentPrice ? "+" : "",
                        price: s.prices.discountedPrice.amount,
                        regularPrice: s.prices.oldPrice.amount || s.prices.finalPrice.amount,
                        isSp: 1*s.prices.finalPrice.amount < 1*s.prices.oldPrice.amount,
                        formattedPrice: priceUtils.formatPrice(
                            s.prices.finalPrice.amount,
                            priceFormat
                        ),
                        formattedBasePrice: priceUtils.formatPrice(
                            s.prices.oldPrice.amount,
                            priceFormat
                        )
                    });
                }
            });

            
            this.currentSelection({
                selectionId: selectedId,
                name: selected.name,
                image: selected.image,
                sku: selected.sku,
                productUrl: selected.productUrl,
                type: selected.type,
                configurableConfig: selected.configurableConfig,
                swatchConfig: selected.swatchConfig,
                sizeConfig: selected.sizeConfig,
                optionTitle: option.title,
                price: selected.prices.discountedPrice.amount,
                regularPrice: selected.prices.oldPrice.amount || selected.prices.finalPrice.amount,
                isSp: 1*selected.prices.finalPrice.amount < 1*selected.prices.oldPrice.amount,
                formattedPrice: priceUtils.formatPrice(
                    selected.prices.finalPrice.amount,
                    priceFormat
                ),
                formattedBasePrice: priceUtils.formatPrice(
                    selected.prices.oldPrice.amount,
                    priceFormat
                )
            });

            this.swapSelections(list);
            this.modal.openModal();
        },

        initSwatches: function(element, product) {
            if (!product.configurableConfig) {
                return;
            }

            $(element).SwatchRenderer({
                jsonConfig: product.configurableConfig,
                jsonSwatchConfig: product.swatchConfig,
                mediaCallback: function(){},
                gallerySwitchStrategy: 'replace',
                jsonSwatchImageSizeConfig: product.sizeConfig
            });
        },

        selectSwap: function (item) {
            var optionId = this.currentOptionId;

            this.bundleJson.selected[optionId] = [item.selectionId];
            this.collectSelectedItems();

            /* Sync with native radios */
            var radio = document.querySelector(
                'input[name="bundle_option[' + optionId + ']"][value="' + item.selectionId + '"]'
            );
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }

            this.modal.closeModal();
        }
    });
});
