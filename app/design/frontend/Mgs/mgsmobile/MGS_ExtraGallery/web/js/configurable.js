/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/translate',
    'priceUtils',
    'priceBox',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'zoom-images',
    'mgsslick', 
    'mgsowlcarousel',
    'magnificPopup'
], function ($, _, mageTemplate) {
    'use strict';
    
    return function (widget) {
        $.widget('mage.configurable', widget, {
            

            /**
             * Initialize tax configuration, initial settings, and options values.
             * @private
             */
            _initializeOptions: function () {
                var options = this.options,
                    gallery = $(options.mediaGallerySelector),
                    galleryTemplate = $('#mgs_template_layout').val(),
                    priceBoxOptions = $(this.options.priceHolderSelector).priceBox('option').priceConfig || null;

                if (priceBoxOptions && priceBoxOptions.optionTemplate) {
                    options.optionTemplate = priceBoxOptions.optionTemplate;
                }

                if (priceBoxOptions && priceBoxOptions.priceFormat) {
                    options.priceFormat = priceBoxOptions.priceFormat;
                }
                options.optionTemplate = mageTemplate(options.optionTemplate);

                options.settings = options.spConfig.containerId ?
                    $(options.spConfig.containerId).find(options.superSelector) :
                    $(options.superSelector);

                options.values = options.spConfig.defaultValues || {};
                options.parentImage = $('[data-role=base-image-container] img').attr('src');

                this.inputSimpleProduct = this.element.find(options.selectSimpleProduct);
                
                var currentImages = [];   
                
                $(".product.media .item-image").each(function( index ) {
                    var item = [];
                    var url_video = "";
                    var type = 'image';
                    
                    if($(this).find('.popup-video').length){
                        url_video = $(this).find('.popup-video').attr('href');
                    }else if($(this).find('.lb.video-link').length){
                        url_video = $(this).find('.lb.video-link').attr('href');
                    }
                    if(url_video){
                        type = 'external-video';
                    }
                    
                    item['zoom'] = $(this).attr('data-zoom');
                    item['full'] = $(this).find('img').attr('src');
                    item['thumb'] = $(this).find('img').attr('src');
                    item['media_type'] = type;
                    item['videoUrl'] = url_video;
                    currentImages.push(item);
                });
                
                options.mediaGalleryInitial = currentImages;
            },
            /**
             * Change displayed product image according to chosen options of configurable product
             * @private
             */
            _changeProductImage: function () {
                var images,
                    imagesToUpdate,
                    initialImages = this.options.mediaGalleryInitial,
                    lbox_image = $('#lbox_image').val();

                if (this.options.spConfig.images[this.simpleProduct]) {
                    images = $.extend(true, [], this.options.spConfig.images[this.simpleProduct]);
                }
                if (images) {
                    imagesToUpdate = images;
                }else {
                    imagesToUpdate = initialImages;
                }
				
				this.updateBaseImageOwl(imagesToUpdate);
				
				if(lbox_image == 1){
					this.lightBoxGallery();
				}
            },
			
			updateBaseImageOwl: function(imagesToUpdate) {
				var img_change = "";
				var view_type = $('#view_type').val();
				
				img_change = '<div id="owl-carousel-gallery" class="owl-carousel gallery-horizontal">'+this.generateHtmlImage(imagesToUpdate)+'</div>';
				
				$(".product.media").html(img_change);
				
				$('#owl-carousel-gallery').owlCarousel({
					items: 1,
					autoplay: false,
					lazyLoad: false,
					nav: false,
					dots: true,
					rtl: $_rtlConfig,
				});
			},
			
			lightBoxGallery: function(){
				$('.product.media').magnificPopup({
					delegate: '.imgzoom .lb',
					type: 'image',
					tLoading: 'Loading image #%curr%...',
					mainClass: 'mfp-img-gallery',
					fixedContentPos: true,
					gallery: {
						enabled: true,
						navigateByImgClick: true,
						preload: [0,1]
					},
					iframe: {
						markup: '<div class="mfp-iframe-scaler">'+
								'<div class="mfp-close"></div>'+
								'<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>'+
								'<div class="mfp-bottom-bar">'+
								  '<div class="mfp-title"></div>'+
								  '<div class="mfp-counter"></div>'+
								'</div>'+
								'</div>'
					},
					image: {
						tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
					},
					callbacks: {
						elementParse: function(item) {
							if(item.el.context.className == 'lb video-link') {
								item.type = 'iframe';
							} else {
								item.type = 'image';
							}
						}
					}
				});
			},
			
			generateHtmlImage: function(imagesToUpdate){
				var html = "",
					lbox_image = $('#lbox_image').val();
				$.each(imagesToUpdate, function(index) {
					var $isVideo = false;
					if((imagesToUpdate[index].media_type == 'external-video' || imagesToUpdate[index].media_type == 'video' || imagesToUpdate[index].type == 'video') && imagesToUpdate[index].videoUrl != ""){
						$isVideo = true;
					}
					
					var $class = 'product item-image imgzoom';
					if($isVideo){
						$class = $class + ' item-image-video';
					}
					html = html + '<div class="'+$class+'" data-zoom="'+imagesToUpdate[index].zoom+'">';
					if($isVideo){
						html = html + '<div class="label-video">'+$.mage.__('Video')+'</div>';
					}
					
					if(lbox_image == 1){
						var href = imagesToUpdate[index].zoom;
						var cla = 'lb';
						if($isVideo){
							href = imagesToUpdate[index].videoUrl;
							cla = 'lb video-link';
						}
						html = html + '<a href="'+href+'" class="'+cla+'"><img class="img-fluid" src="'+imagesToUpdate[index].full+'" alt=""/></a>';
					}else {
						html = html + '<img class="img-fluid" src="'+imagesToUpdate[index].full+'" alt=""/>';
						if($isVideo){
							html = html + '<a target="_blank" class="popup-video" href="'+imagesToUpdate[index].videoUrl+'"><span class="ti-video-camera"></span></a>';
						}
					}
					
					html = html + '</div>';
				});
				return html;
			}
        });
            
        return $.mage.configurable;
    }
});
