require([
    'jquery',
	'MGS_AjaxCart/js/config',
    'Magento_Ui/js/modal/modal'
], function($, mgsConfig, modal) {
    $(document).on("click","#product-addtocart-button",function() {
		var $_this = jQuery(this);
        var form = jQuery('#product_addtocart_form');
        var isValid = form.valid();
        if(isValid){
            $_this.addClass('disabled tocart-loading');
            var data = form.serializeArray();
			var formData = new FormData();
            for(var i = 0; i < data.length; i++){
                formData.append(data[i].name, data[i].value);
            }
            var files = $('input[type="file"]');
            files.each(function(index){
                formData.append(files[index].name, files[index].files[0]);
            });
            formData.append('ajax', 1);
            jQuery.ajax({
                url: form.attr('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                contentType: false,       
                cache: false,             
                processData:false,
                success: function(response, status) {
					$_this.removeClass('disabled tocart-loading');
                    if (status == 'success') {
                        if (response.ui) {
							var $animatedObject = jQuery('<div class="flycart-animated-add" style="position: absolute;z-index: 99999;">'+response.image+'</div>');
							
							var left = $_this.offset().left;
							var top = $_this.offset().top;
							
							$animatedObject.css({top: top-1, left: left-1});
							jQuery('html').append($animatedObject);
							
							var gotoX = jQuery("#cart-top-action").offset().left;
							var gotoY = jQuery("#cart-top-action").offset().top;
							
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
                        }
                    }
                }
            });
            return false;
        }else {
			jQuery('#product-addtocart-button').removeClass('disabled tocart-loading');
		}
    });
});