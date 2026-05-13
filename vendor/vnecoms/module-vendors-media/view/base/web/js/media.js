/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'uiComponent',
    'jquery',
    'underscore',
    'mageUtils',
    'Magento_Ui/js/lib/validation/validator',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'jquery/file-uploader',
    'domReady!'
], function (Component, $, _, utils, validator, uiModal, uiAlert, $t ) {
    'use strict';

    return Component.extend({
        defaults: {
            fileInput: '',
            availablePageSize: [24, 48, 96, 120],
            images: {},
            enableLog: false,
            maxFileSize: false,
            isMultipleFiles: false,
            allowedExtensions: false,
            dropZone: '[data-role=drop-zone]',
            isLoading: false,
            pageSize: 20,
            vendorId: '',
            uploaderConfig: {
                dataType: 'json',
                sequentialUploads: true,
                formData: {
                    'form_key': window.FORM_KEY
                }
            },
            /*When these values are changed, the template that uses these variables will be changed too*/
            tracks: {
                isLoading: true,
            },
            listens: {
                /*The variable isSelectAll must be defined in initObservable method*/
                /*isSelectAll: 'toggleSelectAll',*/
            }
        },
        /**
         * Initialize
         */
        initialize: function () {
            this._super();
            if (Array.isArray(this.images)) {
                this.images = {};
            }
            this.observe({
                uploadFiles:{},
                uploading: false,
                fileUploading: '',
                uploadedImages:this.images,
                currentPage: 1,
                pageSize: this.pageSize,
                errorCount: 0,
                selectedItems: []
            });
            
        },
        
        /**
         * Initializes file uploader plugin on provided input element.
         *
         * @param {HTMLInputElement} fileInput
         * @returns {FileUploader} Chainable.
         */
        initUploader: function (fileInput) {
            this.fileInput = fileInput;
            _.extend(this.uploaderConfig, {
                dropZone:   $(fileInput).closest(this.dropZone),
                change:     this.onFilesChoosed.bind(this),
                drop:       this.onFilesChoosed.bind(this),
                add:        this.onBeforeFileUpload.bind(this),
                done:       this.onFileUploaded.bind(this),
                start:      this.onLoadingStart.bind(this),
                stop:       this.onLoadingStop.bind(this)
            });
            $(fileInput).fileupload(this.uploaderConfig);
            return this;
        },
        /**
         * Get uploaded Images
         */
        getUploadedImages: function () {
            var uploadedImages = this.uploadedImages();
            var result = [];
            var count = 0;
            var curPage = this.currentPage();
            var pageSize = this.pageSize();
            
            for (var x in uploadedImages) {
                if ((count >= (curPage - 1)*pageSize) && (count < curPage*pageSize)) {
                    result.push(uploadedImages[x]); }
    
                if (count > (curPage +1)*pageSize) {
                	break; 
				}
                count ++;
            };
            return result;
        },
        /**
         * Get total number of uploaded images.
         */
        getImagesCount: function () {
            return Object.keys(this.uploadedImages()).length;
        },
        /**
         * Get Total Pages
         */
        getTotalPages: function () {
            var totalPage = this.getImagesCount()/this.pageSize();
            var roundedTotalPage = Math.round(totalPage);
            return totalPage > roundedTotalPage?roundedTotalPage+1:roundedTotalPage;
        },
        /**
         * Navigate to next page
         */
        nextPage: function () {
            var currentPage = this.currentPage();
            if (currentPage >= this.getTotalPages()) {
            	return;
            }
            this.currentPage(++currentPage);
        },
        /**
         * Navigate to previous page
         */
        previousPage: function () {
            var currentPage = this.currentPage();
            if (currentPage <= 1) {
        		return;
            }
            this.currentPage(--currentPage);
        },
        /**
         * Is in first page
         */
        isFirst: function () {
            return this.currentPage() <= 1;
        },
        /**
         * Is in last page
         */
        isLast: function () {
            return this.currentPage() >= this.getTotalPages();
        },
        /**
         * View Image
         */
        viewImage: function (image) {
        	var html = '<div class="thumbnail-preview-image-block"><img class="thumbnail-preview-image" src="'+image.url+'" /></div>';
        	html += '<input type="text" class="input-text implementation-code" value=\'<img src="{{image url="'+this.vendorId+'/'+image.name+'"}}" />\' />';
        	html +='<p class="text-left"><i class="fa fa-lightbulb-o text-yellow"></i> '+$t('Copy the code above and use it on your page to display the image')+'</p>'
            $('<div class="thumbnail-preview import-view-image"></div>').html(html)
            .modal({
                title: image.name,
                type: 'popup',
                modalClass: '_image-box',
                autoOpen: true,
                innerScroll: true,
                buttons: [
                    
                ],
                closed: function () {
                    // on close
                }
             });
        },
        
        /**
         * Is selected Image
         */
        isSelectedImage: function(image){
        	var selectedItems = this.selectedItems();
        	return selectedItems.indexOf(image.name) !== -1;
        },
        
        /**
         * Toggle checkbox
         */
        toggleCheckbox: function(image, imageObj, event){
        	if(
    			event.target.match('.thumb') ||
    			event.target.match('.image a')
			) return;
        	
        	var selectedItems = this.selectedItems();
        	var index = selectedItems.indexOf(image.name);
        	if(index !== -1){
        		selectedItems.splice(index, 1);
        	}else{
        		selectedItems.push(image.name);
        	}
        	
        	this.selectedItems(selectedItems);
        },
        /**
         * Get pending upload files
         *
         * @return array
         */
        getPendingUploadFiles: function () {
            var uploadFiles = this.uploadFiles();
            var result = [];
            for (var x in uploadFiles) {
                var file = uploadFiles[x];
                if (!file.isUploaded) {
                	result.push(file);
                }
            };
            return result;
        },
        /**
         * Start uploader
         */
        startUpload: function () {
            this.uploading(true);
            this.upload();
        },
        /**
         * Stop uploader
         */
        stopUpload: function () {
            this.uploading(false);
        },
        /**
         * Cancel upload
         */
        cancelUpload: function () {
            this.uploading(false);
            this.uploadFiles({});
        },
        isSelectAll: function () {
            var selectedItems = this.selectedItems();
            return this.getImagesCount() && selectedItems.length == this.getImagesCount();
        },
        /**
         * Toggle Select All
         */
        toggleSelectAll: function () {
            this.isSelectAll()?this.deselectAll():this.selectAll();
        },
        /**
         * can select all items
         */
        canSelectAll: function () {
            return !this.isSelectAll();
        },
        /**
         * Select all items.
         */
        selectAll: function () {
            var selectedItems = this.selectedItems();
            var uploadedImages = this.uploadedImages();

            for (var x in uploadedImages) {
                var image = uploadedImages[x];
                if (selectedItems.indexOf(image.name) == -1) {
                    selectedItems.push(image.name);
                }
            };
            this.selectedItems(selectedItems);
            return this;
        },
        /**
         * can deselect all items
         */
        canDeselectAll: function () {
            var selectedItems = this.selectedItems();
            return selectedItems.length;
        },
        /**
         * Select all items.
         */
        deselectAll: function () {
            this.selectedItems([]);
            return this;
        },
        /**
         * can select all items in current page
         */
        canSelectAllOnThisPage: function () {
            var selectedItems = this.selectedItems();
            var result = false;
            this.getUploadedImages().each(function (image) {
                if (selectedItems.indexOf(image.name) == -1) {
                    result = true;
                    return false;/*Break out of foreach*/
                }
            });
            
            return result && this.getTotalPages() >1;
        },
        /**
         * Select all items in current page.
         */
        selectAllOnThisPage: function () {
            var selectedItems = this.selectedItems();
            this.getUploadedImages().each(function (image) {
                if (selectedItems.indexOf(image.name) == -1) {
                    selectedItems.push(image.name);
                }
            });
            this.selectedItems(selectedItems);
            return this;
        },
        /**
         * can deselect all items in current page
         */
        canDeselectAllOnThisPage: function () {
            var selectedItems = this.selectedItems();
            var result = false;
            this.getUploadedImages().each(function (image) {
                if (selectedItems.indexOf(image.name) != -1) {
                    result = true;
                    return false; /*Break out of foreach*/
                }
            });
            
            return result && selectedItems.length && this.getTotalPages() >1;
        },
        /**
         * Deselect all items in current page
         */
        deselectAllOnThisPage: function () {
            var selectedItems = this.selectedItems();
            this.getUploadedImages().each(function (image) {
                var index = selectedItems.indexOf(image.name);
                if (index != -1) {
                    selectedItems.splice(index,1);
                }
            });
            this.selectedItems(selectedItems);
            return this;
        },
        /**
         * Delete selected Images
         */
        deleteImages: function () {
            var self = this;
            this.isLoading = true;
            var selectedItems = this.selectedItems();
            if (!selectedItems.length) {
                uiAlert({
                    title: $t('Error'),
                    content: $t('Please select items.')
                })
                return;
            }
            selectedItems = selectedItems.join(',');
            $.ajax({
                url: self.delete_url,
                type: "POST",
                dataType: 'json',
                data: {files:selectedItems},
                success: function (result) {
                    self.isLoading = false;
                    if (result.error) {
                        uiAlert({
                            title: 'Error',
                            content: result.error
                        })
                    } else if (result.deleted_files) {
                        /*Reset selected items*/
                        self.selectedItems([]);
                        /* Delete all deleted items from the list*/
                        result.deleted_files.each(function (fileName) {
                            self.removeFile(fileName);
                        });
                        /* If current page greater than total page just jump to first page*/
                        if (self.currentPage() > self.getTotalPages()) {
							self.currentPage(1);
						}
                    }
                }
            });
        },
        /**
         * Upload action
         */
        upload: function () {
            var self = this;
            if (!this.uploading()) {
            	return; 
			}
            var uploadFile = false;
            var uploadFiles = this.uploadFiles();
            var index;
            for (index in uploadFiles) {
                var file = uploadFiles[index];
                if (!file.isUploaded && !file.errorMsg) {
					uploadFile = file;
					break;
				}
            };
            
            if (!uploadFile) {
                this.uploading(false);
                this.fileUploading('');
                return;
            };
            this.fileUploading(uploadFile.file.name);
            var jqXHR = uploadFile.data.submit()
            .success(function (result, textStatus, jqXHR) {
            	self.upload();
			})
            .error(function (jqXHR, textStatus, errorThrown) {})
            .complete(function (result, textStatus, jqXHR) {
        	});
        },
        /**
         * Check if a file is uploading
         */
        isFileUploading(fileInfo){
            return fileInfo.file.name == this.fileUploading();
        },
        /**
         * Get error message of a file
         */
        getErrorMessage: function (fileInfo) {
            var uploadFiles = this.uploadFiles();
            return uploadFiles[fileInfo.file.name].errorMsg;
        },
        /**
         * Show error message
         */
        showErrorMessage: function (fileInfo) {
            uiAlert({
                title: 'Error',
                content: this.getErrorMessage(fileInfo)
            })
        },
        /**
         * Adds provided file to the files list.
         *
         * @param {Object} file
         * @returns {FileUploder} Chainable.
         */
        addFile: function (file) {
            var uploadedImages = this.uploadedImages();
            uploadedImages[file.name] = file;
            this.uploadedImages(uploadedImages)
            return this;
        },
        /**
         * Remove the file from uploaded images
         */
        removeFile: function (fileName) {
            var uploadedImages = this.uploadedImages();
            delete uploadedImages[fileName];
            this.uploadedImages(uploadedImages);
            return this;
        },
        /**
         * Formats incoming bytes value to a readable format.
         *
         * @param {Number} bytes
         * @returns {String}
         */
        formatSize: function (bytes) {
            var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'],
                i;

            if (bytes === 0) {
                return '0 Byte';
            }

            i = window.parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));

            return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
        },

        /**
         * Checks if provided file is allowed to be uploaded.
         *
         * @param {Object} file
         * @returns {Object} Validation result.
         */
        isFileAllowed: function (file) {
            var result;

            _.every([
                this.isExtensionAllowed(file),
                this.isSizeExceeded(file)
            ], function (value) {
                result = value;

                return value.passed;
            });

            return result;
        },

        /**
         * Checks if extension of provided file is allowed.
         *
         * @param {Object} file - File to be checked.
         * @returns {Boolean}
         */
        isExtensionAllowed: function (file) {
            return validator('validate-file-type', file.name, this.allowedExtensions);
        },

        /**
         * Checks if size of provided file exceeds
         * defined in configuration size limits.
         *
         * @param {Object} file - File to be checked.
         * @returns {Boolean}
         */
        isSizeExceeded: function (file) {
            return validator('validate-max-size', file.size, this.maxFileSize);
        },

        /**
         * Displays provided error message.
         *
         * @param {String} msg
         * @returns {FileUploader} Chainable.
         */
        notifyError: function (msg, title=$t('Error: ')) {
            this.errorCount(this.errorCount() + 1);
            $('#tab_error').prepend('<div class="message message message-error error"><div>'+title +'<br />'+ msg+'<div></div>');
            return this;
        },

        /**
         * Abstract handler which is invoked when files are choosed for upload.
         * May be used for implementation of aditional validation rules,
         * e.g. total files and a total size rules.
         *
         * @abstract
         */
        onFilesChoosed: function () {
            if (this.enableLog) {
            	console.log('file choosed');
            }
        },

        /**
         * Handler which is invoked prior to the start of a file upload.
         *
         * @param {Event} e - Event obejct.
         * @param {Object} data - File data that will be uploaded.
         */
        onBeforeFileUpload: function (e, data) {
            var self = this;
            
            if (this.enableLog) {
                console.log('Before file upload');
                console.log(e);
                console.log(data);
            }
            var file     = data.files[0],
                allowed  = this.isFileAllowed(file);
            
            if (allowed.passed) {
                $(e.target).fileupload('process', data).done(function () {
                    var uploadFiles = self.uploadFiles();
                    uploadFiles[file.name] = {file: file, isUploaded:false,errorMsg: '', data:data};
                    self.uploadFiles(uploadFiles);
                    
                    /*data.submit();*/
                });
            } else {
                this.notifyError(allowed.message, $t('Error: %1').replace('%1',file.name));
            }
        },

        /**
         * Handler of the file upload complete event.
         *
         * @param {Event} e
         * @param {Object} data
         */
        onFileUploaded: function (e, data) {
            if (this.enableLog) {
                console.log('file uploaded');
                console.log(e);
                console.log(data);
            }
            var file    = data.result,
                error   = file.error;
            
            var uploadFiles = this.uploadFiles();
            if (uploadFiles[file.name]) {
                uploadFiles[file.name].isUploaded = !error;
                uploadFiles[file.name].errorMsg = error;
                this.uploadFiles(uploadFiles);
            }
            
            error ?
                    this.notifyError(error, $t('Error: %1').replace('%1',file.name)) :
                    this.addFile(file);
        },

        /**
         * Load start event handler.
         */
        onLoadingStart: function () {
            if (this.enableLog) {
                console.log('Loading start');
            }
            this.isLoading = true;
        },

        /**
         * Load stop event handler.
         */
        onLoadingStop: function () {
            if (this.enableLog) {
                console.log('Loading Stop');
            }
            this.isLoading = false;
        },

        /**
         * Handler function which is supposed to be invoked when
         * file input element has been rendered.
         *
         * @param {HTMLInputElement} fileInput
         */
        onElementRender: function (fileInput) {
            this.initUploader(fileInput);
        },
        /**
         * Handler of the preview image load event.
         *
         * @param {Object} file - File associated with an image.
         * @param {Event} e
         */
        onPreviewLoad: function (file, e) {
            var img = e.currentTarget;

            file.previewWidth = img.naturalHeight;
            file.previewHeight = img.naturalWidth;
        }
    });
});

