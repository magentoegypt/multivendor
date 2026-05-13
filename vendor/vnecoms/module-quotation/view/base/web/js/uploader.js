/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define(
    [
        'Magento_Ui/js/lib/validation/validator',
        'Magento_Ui/js/form/element/file-uploader',
        'ko'
    ],
    function (
        validator,
        UpLoader,
        ko
    ) {
        return UpLoader.extend({
            defaults: {
                isMultipleFiles : true,
                inputName: 'image',
                links: {
                    value: '${ $.parentName }:uploadValue'
                }
            },

            /**
             * Invokes initialize method of parent class,
             * contains initialization logic
             */
            initialize: function () {
                _.bindAll(this, 'reset');

                this._super()
                    .setInitialValue()
                    ._setClasses()
                    .initSwitcher();

                return this;
            },
            
            /**
             * Is Image File
             */
            isImage: function(filename){
            	var extension = filename.split('.').pop().toLowerCase();
            	return ['png','jpg','jpeg','gif'].indexOf(extension) != -1;
            },
            
            /**
             * Get file class name
             */
            getClassIcon:function (filename){
            	var extension = filename.split('.').pop().toLowerCase();
            	switch(extension){
            		case 'rar':
            		case 'tgz':
            		case 'bz':
	                case 'zip':
	                    return 'quote-icon-file-zip';
	                case 'pdf':
	                    return 'quote-icon-file-pdf';
	                case 'doc':
	                case 'docx':
	                    return 'quote-icon-file-word';
	                case 'xls':
	                case 'xlsx':
	                    return 'quote-icon-file-excel';
	                case 'png':
	                case 'jpeg':
	                case 'jpg':
	                case 'gif':
	                    return 'quote-icon-file-image';
	                default: return 'quote-icon-file-empty';
	            }
            }

        });
    }
);