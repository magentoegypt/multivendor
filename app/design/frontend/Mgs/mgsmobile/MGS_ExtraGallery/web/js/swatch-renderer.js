define([
    'jquery',
    'underscore',
    'jquery/ui',
    'jquery/jquery.parsequery',
    'zoom-images',
    'mage/translate',
    'mgsslick',
    'mgsowlcarousel',
    'magnificPopup'
], function ($) {
    'use strict';

    return function (widget) {

        $.widget('mage.SwatchRenderer', widget, {
            /**
			 * @private
			 */
			_init: function () {
				if (this.options.jsonConfig !== '' && this.options.jsonSwatchConfig !== '') {
					this._sortAttributes();
					this._RenderControls();
				} else {
					console.log('SwatchRenderer: No input data received');
				}

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
					item['full'] = $(this).find('.img-fluid').attr('src');
					item['thumb'] = $(this).find('.img-fluid').attr('src');
					item['media_type'] = type;
					item['videoUrl'] = url_video;
					currentImages.push(item);
				});

				this.options.mediaGalleryInitial = currentImages;
			},

			/**
			 * Callback for product media
			 *
			 * @param {Object} $this
			 * @param {String} response
			 * @private
			 */
			_ProductMediaCallback: function ($this, response) {
				var isProductViewExist = $('body.catalog-product-view').length > 0;
				if($('.product_quickview_content').length > 0){
					isProductViewExist = 1;
				}
				var $main = isProductViewExist ? $this.parents('.column.main') : $this.parents('.product-item-info'),
					$widget = this,
					images = [],

					/**
					 * Check whether object supported or not
					 *
					 * @param {Object} e
					 * @returns {*|Boolean}
					 */
					support = function (e) {
						return e.hasOwnProperty('large') && e.hasOwnProperty('medium') && e.hasOwnProperty('small');
					};

				if (_.size($widget) < 1 || !support(response)) {
					this.updateBaseImage(this.options.mediaGalleryInitial, $main, isProductViewExist);

					return;
				}

				images.push({
					full: response.large,
					img: response.medium,
					thumb: response.small,
					zoom: response.zoom,
					media_type: response.media_type,
					videoUrl: response.video_url,
					isMain: true
				});

				if (response.hasOwnProperty('gallery')) {
					$.each(response.gallery, function () {
						if (!support(this) || response.large === this.large) {
							return;
						}
						images.push({
							full: this.large,
							img: this.medium,
							zoom: this.zoom,
							thumb: this.small,
							media_type: this.media_type,
							videoUrl: this.video_url
						});
					});
				}

				this.updateBaseImage(images, $main, isProductViewExist);
			},


			/**
			 * Update [gallery-placeholder] or [product-image-photo]
			 * @param {Array} images
			 * @param {jQuery} context
			 * @param {Boolean} isProductViewExist
			 */
			updateBaseImage: function (images, context, isProductViewExist) {
				var justAnImage = images[0],
					updateImg,
					imagesToUpdate,
					gallery = context.find(this.options.mediaGallerySelector).data('gallery'),
					lbox_image = $('#lbox_image').val(),
					item;
				if (isProductViewExist) {

					imagesToUpdate = images.length ? images : [];

					this.updateBaseImageOwl(imagesToUpdate);

					if(lbox_image == 1){
						this.lightBoxGallery();
					}

				} else if (justAnImage && justAnImage.img) {
					context.find('.product-image-photo').attr('src', justAnImage.img);
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
							if($(item.el).hasClass('video-link')) {
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
					html = html + '<div class="'+$class+'" data-zoom="'+imagesToUpdate[index].full+'">';
					if($isVideo){
						html = html + '<div class="label-video">'+$.mage.__('Video')+'</div>';
					}

                    var imgBase = imagesToUpdate[index].img ? imagesToUpdate[index].img : imagesToUpdate[index].full;

					if(lbox_image == 1){
						var href = imagesToUpdate[index].full;
						var cla = 'lb';
						if($isVideo){
							href = imagesToUpdate[index].videoUrl;
							cla = 'lb video-link';
						}
						html = html + '<a href="'+href+'" class="'+cla+'"><img class="img-fluid" src="'+imgBase+'" alt=""/></a>';
					}else {
						html = html + '<img class="img-fluid" src="'+imgBase+'" alt=""/>';
						if($isVideo){
							html = html + '<a target="_blank" class="popup-video" href="'+imagesToUpdate[index].videoUrl+'"><span class="ti-video-camera"></span></a>';
						}
					}

					html = html + '</div>';
				});
				return html;
			}
        });

        return $.mage.SwatchRenderer;
    }
});
