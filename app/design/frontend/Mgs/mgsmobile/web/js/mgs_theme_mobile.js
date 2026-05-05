require([
	'jquery',
	'magnificPopup',
	'mgsowlcarousel',
	'mage/translate'
], function ($) {
	
	/********************** FOOTER ***************************/
	$(document).on("click",".acc-block .footer-title",function(){
		console.log("clicked");
		$(this).parent().toggleClass('active');
		$(this).parent().find('ul').slideToggle();
	});
	/*********************************************************/
	
	/********************** FILTER ***************************/
	$(document).ready(function(){
		if($('#layered-filter-block').length){
			var $_filterAction = '<div class="filter-placeholder"><a class="action-filter" href="javascript:void(0)">'+$.mage.__('Filter')+'</a></div>';
			$('.top-toolbar .toolbar-products .toolbar-sorter').before($_filterAction);
		}
	});
	
	$(document).on("click",".action-filter",function(e){
		$('#layered-filter-block').addClass('active');
	});
	
	$(document).on("click",".block-subtitle.filter-subtitle",function(e){
		$('#layered-filter-block').removeClass('active');
	});
	
	/*********************************************************/
	
	/*************** BOTTOM ACTION HEADER ********************/
	var $_miniCart = $("header .minicart-wrapper");
	var $_loginForm = $("header .login-form");
	var $_settingSite = $("header .setting-site-content");
	var $_wishlistHeader = $("header .top-wishlist");
	
	$(document).on("click","#js_mobile_tabs button",function(e){
		var $el_action = $(this).attr('id');
		if(!$(this).hasClass('active-child')){
			$('#js_mobile_tabs button.active-child').removeClass('active-child');
			$(this).addClass('active-child');
			$('.page-header').removeClass('active-menu');
			
			switch ($el_action) {
				case "back-home-action":
					updateAnimation($_miniCart, "");
					updateAnimation($_loginForm, "");
					updateAnimation($_settingSite, "");
					updateAnimation($_wishlistHeader, "");
					setTimeout(function(){ $('body').removeClass('atv-cart atv-setting atv-myaccount atv-wishlist atv-sidebar'); }, 300);
					break;
				case "my-account-action":
					$('body').addClass('atv-myaccount');
					updateAnimation($_loginForm, "translateX(0)");
					updateAnimation($_miniCart, "");
					updateAnimation($_settingSite, "");
					updateAnimation($_wishlistHeader, "");
					setTimeout(function(){ $('body').removeClass('atv-cart atv-setting atv-wishlist atv-sidebar'); }, 300);
					break;
				case "wishlist-link-action":
					$('body').addClass('atv-wishlist');
					updateAnimation($_loginForm, "translateX(-100%)");
					updateAnimation($_wishlistHeader, "translateX(0)");
					updateAnimation($_miniCart, "");
					updateAnimation($_settingSite, "");
					setTimeout(function(){ $('body').removeClass('atv-cart atv-setting atv-myaccount atv-sidebar'); }, 300);
					break;
				case "cart-top-action":
					$('body').addClass('atv-cart');
					updateAnimation($_loginForm, "translateX(-100%)");
					updateAnimation($_wishlistHeader, "translateX(-100%)");
					updateAnimation($_miniCart, "translateX(0)");
					updateAnimation($_settingSite, "");
					setTimeout(function(){ $('body').removeClass('atv-myaccount atv-setting atv-wishlist atv-sidebar'); }, 300);
					break;
				case "setting-web-action":
					$('body').addClass('atv-setting');
					updateAnimation($_loginForm, "translateX(-100%)");
					updateAnimation($_miniCart, "translateX(-100%)");
					updateAnimation($_wishlistHeader, "translateX(-100%)");
					updateAnimation($_settingSite, "translateX(0)");
					setTimeout(function(){ $('body').removeClass('atv-cart atv-myaccount atv-wishlist atv-sidebar'); }, 300);
					break;
			}
		}else {
			$(this).removeClass('active-child');
			updateAnimation($_miniCart, "");
			updateAnimation($_loginForm, "");
			updateAnimation($_settingSite, "");
			updateAnimation($_wishlistHeader, "");
			setTimeout(function(){ $('body').removeClass('atv-cart atv-setting atv-myaccount atv-wishlist atv-sidebar'); }, 300);
		}
	});
	
	function updateAnimation($el, $value) {
		$el.css('transform', $value);
		$el.css('-webkit-transform', $value);
		$el.css('-moz-transform', $value);
		$el.css('-o-transform', $value);
	}
	/*********************************************************/
	
	/********************** SEARCH ***************************/
	$(document).on("click",".block-search > .block-title",function(e){
		e.preventDefault();
		$(this).parent().toggleClass('active');
	});
	/*********************************************************/
	
	/********************* MINICART **************************/
	$(document).on("click",".minicart-wrapper .details-qty .minus",function(e){
		var $itemQty = parseInt($(this).parent().find('.cart-item-qty').attr('data-item-qty'));
		var $val = parseInt($(this).parent().find('.cart-item-qty').val());
		var $valChange = $val - 1;
		if($val > 1){
			$(this).parent().find('.cart-item-qty').val($valChange);
			if($itemQty != $valChange){
				$(this).parents('.details-qty').find('.update-cart-item').show('fade', 300);
			}else {
				$(this).parents('.details-qty').find('.update-cart-item').hide('fade', 300);
			}
		}
	});
	
	$(document).on("click",".minicart-wrapper .details-qty .plus",function(e){
		var $val = parseInt($(this).parent().find('.cart-item-qty').val());
		var $itemQty = parseInt($(this).parent().find('.cart-item-qty').attr('data-item-qty'));
		var $valChange = $val + 1;
		$(this).parent().find('.cart-item-qty').val($valChange);
		if($itemQty != $valChange){
			$(this).parents('.details-qty').find('.update-cart-item').show('fade', 300);
		}else {
			$(this).parents('.details-qty').find('.update-cart-item').hide('fade', 300);
		}
	});
	/*********************************************************/
	
	/********************* MEGAMENU **************************/
	$(document).on("click",".megamenu_action_mb",function(e){
		$('header.page-header').toggleClass('active-menu');
	});
	
	$(document).on("click","#close-menu-site",function(e){
		$('header.page-header').removeClass('active-menu');
	});
	
	$(document).on("click",".horizontal-menu li > .toggle-menu",function(e){
		if($(this).parent().find('> .dropdown-mega-menu').length){
			$(this).parent().find('> .dropdown-mega-menu').slideToggle();
			$(this).parent().toggleClass('_show-child');
		}else if($(this).parent().find('> .sub-menu, > .dropdown-menu-ct').length){
			$(this).parent().find('> .sub-menu, > .dropdown-menu-ct').slideToggle();
			$(this).parent().toggleClass('_show-child');
		}
	});
	/*********************************************************/
	
	/*********************** MY WISHLIST *********************/
	$(document).on("click",".box-tocart .qty .control .minus",function(e){
		var $itemQty = parseInt($(this).parent().find('input.qty').attr('data-item-qty'));
		var $val = parseInt($(this).parent().find('input.qty').val());
		var $valChange = $val - 1;
		if($valChange >= 0){
			$(this).parent().find('input.qty').val($valChange);
		}
	});
	
	$(document).on("click",".box-tocart .qty .control .plus",function(e){
		var $itemQty = parseInt($(this).parent().find('input.qty').attr('data-item-qty'));
		var $val = parseInt($(this).parent().find('input.qty').val());
		var $valChange = $val + 1;
		$(this).parent().find('input.qty').val($valChange);
	});
	
    
	$(document).ready(function(){
		/* Header Sticky Menu */
		if($('.active-sticky').length){
			var headerHeight = $('.active-sticky').height();
			$(window).scroll(function(){
				var st = $(this).scrollTop();
				if(st > headerHeight){
					$('.active-sticky').addClass('start-stk');
					$('.header-area .top-header').slideUp();
				}else {
					$('.active-sticky').removeClass('start-stk');
					$('.header-area .top-header').slideDown();
				}
			});
		}
		$(".form-create-account .field:not('.choice') > .control > input, .form-create-account .field:not('.choice') select").each(function() {
			$(this).focusin(function(){
				$(this).parent().parent().find(' > .label').addClass('input-focus');
			});
			$(this).focusout(function(){
				$(this).parent().find('.label').removeClass('input-focus');
				$(this).blur(function(){
					if( !$(this).val() ) {
						$(this).parent().parent().find('.label').removeClass('input-focus');
					}
				});
			});
		});
		$('.popup-video').magnificPopup({
			type: 'iframe',
			mainClass: 'mfp-img-gallery',
			removalDelay: 160,
			preloader: false,
			fixedContentPos: false
		}); 
		
		$('.js-owl-carousel-product').owlCarousel({
			items: 2,
			autoplay: true,
			nav: false,
			dots: true,
			loop: false,
			lazyLoad: true,
			rtl: $_rtlConfig
		});
	});
});

/* Coming Soon */

require([
	'jquery'
], function($){
	function countdownTimer($_date, $_element){
		setInterval(function(){
			var future = new Date($_date);
			var now = new Date();
			var difference = Math.floor((future.getTime() - now.getTime()) / 1000);
			
			var seconds = fixIntegers(difference % 60);
			difference = Math.floor(difference / 60);
			
			var minutes = fixIntegers(difference % 60);
			difference = Math.floor(difference / 60);
			
			var hours = fixIntegers(difference % 24);
			difference = Math.floor(difference / 24);
			
			var days = difference;
			
			$_element.find('.secs').text(seconds);
			$_element.find('.mins').text(minutes);
			$_element.find('.hours').text(hours);
			$_element.find('.days').text(days);
		}, 1000);
	}
	
	function fixIntegers(integer)
	{
		if (integer < 0)
			integer = 0;
		if (integer < 10)
			return "0" + integer;
		return "" + integer;
	}
});


/* End Coming Soon */

function setLocation(url) {
    require([
        'jquery'
    ], function (jQuery) {
        (function () {
            window.location.href = url;
        })(jQuery);
    });
}