/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'uiComponent',
    'jquery',
    'domReady!'
], function (Component, $) {
    'use strict';

    return Component.extend({
        defaults: {
           is_running: false,
           import_progress: 0,
           total_rows: 0,
           import_url: ''
        },
        initialize: function () {
            this._super()
            .observe({
                isImporting: false,                             /* Is running importing*/
                importProgress: 0,                          /* Import Progress*/
                importedItems: 0,
                resultCount:0,
                errorCount:0
            });
            
        },
        
        runProgressBar: function (importedItem) {
            var totalImportedItem = this.importedItems() + importedItem;
            this.importedItems(totalImportedItem);
            this.importProgress(totalImportedItem * 100 / this.total_rows);
        },
        
        getImportProgress: function () {
            var progress = (this.importProgress() * 100 / this.total_rows);;
            return progress;
        },
        
        execute: function () {
            this.isImporting(!this.isImporting());
            
            this.import();
        },
        pushSuccess: function (messages) {
            this.resultCount(this.resultCount() + messages.size());
            messages.each(function (message) {
                $('#tab_result').prepend('<div class="message message-success success"><div>'+message+'<div></div>');
            });
        },
        pushError: function (messages) {
            this.errorCount(this.errorCount() + messages.size());
            messages.each(function (message) {
                $('#tab_error').prepend('<div class="message message message-error error"><div>'+message+'<div></div>');
            });
        },
        
        import: function () {
            if (this.importedItems() >= this.total_rows) {
                this.isImporting(false);
            }
            
            if (!this.isImporting()) {
return; }
            var self = this;
            $.ajax({
                url: self.import_url,
                type: "POST",
                dataType: 'json',
                data: {},
                processData: false,
                contentType: false,
                success: function (result) {
                    if (!result.processed_items) {
this.isImporting(false); }
                    
                    self.runProgressBar(result.processed_items);
                    $('#import-result-container').show();
                    if (result.messages.success.size()) {
                        self.pushSuccess(result.messages.success);
                    }
                    if (result.messages.error.size()) {
                        self.pushError(result.messages.error);
                    }
                    self.import();
                }
            });
        }
    });
});

