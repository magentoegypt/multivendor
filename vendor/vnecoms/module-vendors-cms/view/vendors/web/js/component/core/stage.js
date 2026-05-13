
define([
    'jquery',
    'vendorscms/hook'
], function (jQuery, Hook) {

    /**
     * The stage is the main hub for any 'page builder' element. It handles all interactions between the sub modules,
     * along side initializing the configuration and other things
     *
     * @constructor
     */
    function Stage()
    {
        this.wysiwyg = false;
        this.textarea = false;
        this.originalContent = false;
        this.container = false;
        this.structural = false;
        this.panel = false; //panel side
        this.config = false;
        this.disabled = false; //state of mode

        this.emptyFieldText = 'TEMPORARY_BLUEFOOT_STRING';
    }

    /**
     * Event callback for stage init
     *
     * @param button
     * @param buildInstance
     */
    Stage.prototype.init = function (button, buildInstance) {
        this.build = buildInstance;

        // Add a loading class to the button
        jQuery(button).addClass('loading stage-init');
        //this.namespace = jQuery(button).data('namespace');
        if (jQuery(button).data('stage')) {
            var stage = jQuery(button).data('stage');
            jQuery(button).removeClass('loading');
            return stage.enableStage();
        }

        // Retrieve the container
        this.id = jQuery(button).attr('id').replace('vendorscms-stage', '');
        var container = jQuery(button).parents('.buttons-set').nextAll('.vendorscms-stage-container');


    };

    /**
     * Build up the stage configuration
     *
     * @param config
     * @returns {{}}
     */
    Stage.prototype.getStageConfig = function (config) {
        return {
            id: this.id
        };
    };

    /**
     * Init the stage
     *
     * @param container
     * @param callback
     */
    Stage.prototype.stageInit = function (container, callback) {

        // Verify the container exists
        if (!container || (container && container instanceof jQuery && container.length == 0)) {
            throw new Error('No valid container present');
        }

        // Generate a token so we can ensure we're building the same page builder after the config load
        var _initToken = Math.random().toString(36).slice(-5),
            categoryId = false;

        this.container = container;
        this.container.data('_initToken', _initToken);
        this.textarea = this.container.next('textarea');
        this.originalContent = this.textarea.val();
        this.textarea.hide();
    };

    /**
     * Handle hiding and showing the buttons
     */
    Stage.prototype.handleButtons = function () {

        // Show and hide the correct buttons
        var buttonsContainer = this.container.prevAll('.buttons-set');
        buttonsContainer.find('button').hide();

        var disableButton = buttonsContainer.find('.action-mode-disable');
        disableButton.off('click').on('click', function () {
            require('bluefoot/modal').confirm('Are you sure you want to disable the BlueFoot?', 'Are you sure?', function () {
                return this.disableStage();
            }.bind(this));
        }.bind(this));
        disableButton.data('vendorscms-hidden', '').show();
    };

    /**
     * Disable a stage, this will hide the stage in the background just in case the user wishes to re-enable the stage
     */
    Stage.prototype.enableStage = function () {
        // Show and hide the correct buttons
        var buttonsContainer = this.container.parent().find('.buttons-set');
        buttonsContainer.find('button.action-mode-disable').show();
        buttonsContainer.find('button.action-mode').hide();
        buttonsContainer.find('#toggle' + this.id).hide();

        this.save.enable();
        this.container.addClass('stage-init').show();
       // buttonsContainer.parents('.admin__field').addClass('gene-bluefoot-admin__field');
        if (typeof this.wysiwyg === 'object') {
            this.wysiwyg.turnOff();
            buttonsContainer.find('.plugin').hide();
        }
        this.textarea.hide();
    };

    /**
     * Disable a stage, this will hide the stage in the background just in case the user wishes to re-enable the stage
     */
    Stage.prototype.disableStage = function () {
        // Show and hide the correct buttons
        var buttonsContainer = this.container.parent().find('.buttons-set');
        buttonsContainer.find('button.action-mode-disable').hide();
        buttonsContainer.find('button.action-mode').show();
        buttonsContainer.find('#toggle' + this.id).show();

        this.disabled = true;
        this.save.disable();
        this.container.removeClass('stage-init').hide();
        buttonsContainer.parents('.admin__field').removeClass('gene-bluefoot-admin__field');
        if (this.textarea.val() == this.emptyFieldText) {
            this.textarea.val('');
        }
        this.textarea.show();
        if (typeof this.wysiwyg === 'object') {
            this.wysiwyg.turnOn();
        }
        jQuery('.mceEditor').width('100%');
    };

    /**
     * Full screen the stage, only can be called from the panel
     */
    Stage.prototype.fullscreen = function () {
        var container = this.stage.container;
        if (this.stage.container.hasClass('full-screen')) {
            jQuery('body').removeClass('bluefoot-locked');
            container.removeClass('full-screen');

            setTimeout(function () {
                jQuery(window).scroll();
            }.bind(this), 0);
        } else {
            jQuery('body').addClass('bluefoot-locked');
            container.addClass('full-screen');

            setTimeout(function () {
                // Trigger scroll to bring the panel to the correct place
                this.stage.container.find('.gene-bluefoot-stage').scroll();
            }.bind(this), 0);
        }
    };

    /**
     * Determine if a store ID is present and set
     *
     * @returns {*}
     */
    Stage.prototype.getStoreId = function () {
        if (jQuery('#store_switcher').length > 0) {
            return jQuery('#store_switcher').val();
        }
    };

    /**
     * Empty the stages content
     */
    Stage.prototype.empty = function () {
        jQuery.each(this.structural.getRows(), function (index, row) {
            jQuery(row).remove();
        });
        Hook.trigger('gene-bluefoot-stage-ui-updated', false, false, this);
        Hook.trigger('gene-bluefoot-stage-updated', false, false, this);
    };

    /**
     * Assign unique ID's to elements as they're added to the stage
     *
     * @param element
     * @param elementClass
     */
    Stage.prototype.recordElement = function (element, elementClass) {
        var id = this.generateElementId();
        jQuery(element).data('class', elementClass).attr('id', id).addClass('gene-bluefoot-entity-container').attr('data-flagged', 'true');
    };

    /**
     * Generate a random 32 character element ID
     *
     * @returns {string}
     */
    Stage.prototype.generateElementId = function () {
        var elementId = '';
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for (var i = 0; i < 32; i++) {
            elementId += possible.charAt(Math.floor(Math.random() * possible.length)); }
        return elementId;
    };

    return Stage;
});