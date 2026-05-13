
/*global define*/
define(
    [
        'ko',
        'uiCollection',
        'mageUtils',
        'uiLayout',
        'underscore',
        'jquery',
        'mage/translate',
        'moment',
        'Magento_Ui/js/modal/alert',
        'Vnecoms_Quotation/js/uploader',
        'Magento_Ui/js/modal/modal',
        'shorten'
    ],
    function(ko, Component, utils, layout, _, $, $t, moment, alert, Uploader) {
        'use strict';
        return Component.extend({
            default: {
                template: 'Vnecoms_Quotation/messages',
                showSpinner: true,
                visible: true,
                disabled: false,
                messageInput: '',
                quote_id: '',
            },
            initialize: function () {
                this._super();
                return this;
            },

            /**
             * Calls 'initObservable' of parent
             *
             * @returns {Object} Chainable.
             */
            initObservable: function () {
                var self = this;
                this._super()
                    .observe([
                        'messageInput',
                        'messages',
                    ]);
                return this;
            },
            /**
             * Get css class for a message
             */
            getMessageCssClass: function(message){
            	switch(parseInt(message.user_type)){
	                case 0:
	                    return 'quote-message-customer';
	                case 1:
	                    return 'quote-message-admin';
	                case 2:
	                    return 'quote-message-vendor';
	            }

            	return 'quote-message-customer';
            },
            /**
             * Get user label
             */
            getUserLabel: function(message){
            	switch(parseInt(message.user_type)){
	                case 0:
	                    return $t('[customer]');
	                case 1:
	                	return $t('[admin]');
	                case 2:
	                	return $t('[vendor]');
	            }

            	return $t('[customer]');
            },
            /**
             * Get All Messages.
             */
            getMessages: function(){
            	return this.messages();
            },

            /**
             * Show preview image modal.
             */
            previewImage: function(message, event){
            	if(
        			$(event.target).is('.action-icon') ||
        			$(event.target).is('.download-action')
    			){
            		window.setLocation(message.download_url);
            		return true;
        		};

        		if(!message.is_image) return;

            	var id = 'quotation-attachment-'+message.id;
           	 	if($('#'+id).length){
	           		 $('#'+id).modal('openModal');
           	 	}else{
   	 			$('<div class="thumbnail-preview" id="'+id+'"></div>').html('<div class="thumbnail-preview-image-block"><img class="thumbnail-preview-image" src="'+message.url+'" /></div>')
					.modal({
					    title: message.name,
					    type: 'popup',
					    modalClass: '_image-box quotation-attachment-modal',
					    autoOpen: true,
					    innerScroll: true,
					    buttons:{}
					});
           	 	}
            },
            /**
             * Get uploaded values
             */
            getUploadedValues: function(){
            	var uploadedValues = [];
            	if(this.regions['uploader']){
            		var uploader = this.regions['uploader']()[0];
            		uploader.value.each(function(file){
            			uploadedValues.push(file.file);
            		});
            	}

            	return uploadedValues.join('||');
            },
            /**
             * Send message
             */
            sendMessage: function () {
        		/*Validate*/
            	if(!this.messageInput()){
            		alert({
            			title: $t('Error'),
            			content: $t('Please enter the message.'),
            		});
            		return;
            	}

                var self = this;
                var txtMessage = self.messageInput();
                var uploadedValues = self.getUploadedValues();
                $.ajax({
                    url: self.addMessageUrl,
                    method: "POST",
                    data: {
                        quote_id: self.quote_id,
                        message: txtMessage,
                        attachments: uploadedValues
                    },
                    showLoader: true,
                    dataType: "json"
                }).done(function (response) {
                	if(response.error){
                		alert({
                			title: $t('Error'),
                			content: response.message,
                		});
                	}else if(!response.error){
                		/*Clear text box*/
                        self.messageInput('');
                        self.resetUploader();
                        /*Add message*/
                        self.addMessage(response.data);
                	}
                }).fail(function () {
                	alert({
	            		title: $t('Error'),
	                    content: $t('Something wrong. Please try to refresh the page.')
	                });
                });
            },
            /**
             * Add message
             */
            addMessage: function(data){
            	var messages = this.messages();
            	messages.unshift(data);
            	this.messages(messages);
            },
            /**
             * Reset uploader
             */
            resetUploader: function(){
            	if(this.regions['uploader']){
            		var uploader = this.regions['uploader']()[0];
            		uploader.value([]);
            	}
            }
        });
    }
);
