/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
/*global define*/
define(
    [
        'underscore',
        'uiComponent',
        'ko',
        'jquery',
        'mage/translate',
        'mage/template',
        'Magento_Catalog/js/price-utils',
        'Magento_Ui/js/modal/alert',
        'domReady!'
    ],
    function(_, Component, ko, $, $t, mageTemplate, utils, alert) {
        'use strict';

        return Component.extend({
            default: {
                template: 'Vnecoms_Quotation/proposal',
                proposalTemplate: 'Vnecoms_Quotation/proposal/proposal',
                qtyTemplate: 'Vnecoms_Quotation/proposal/qty',
                marginGpTemplate: 'Vnecoms_Quotation/proposal/margin',
                actionTemplate: 'Vnecoms_Quotation/proposal/action',
                defaultTemplate: 'Vnecoms_Quotation/proposal/default',
                isLoading: true,
                saveProposalUrl: '',
                updateProposalUrl: '',
                removeProposalUrl: '',
                saveDefaultProposalUrl: '',
                addBtn: $t('Add'),
                removeBtn: $t('Remove'),
                isDefaultLabel: $t('Default'),
                itemId: '',
                defaultProposal: '',
                isEditable: true,
                defaultProposalFlag: true
            },

            initialize: function () {
                this._super();
                this.initProposals(this.proposals());
                this.defaultProposalFlag(true);
                var self = this;
                this.defaultProposal.subscribe(function () {
                	if(self.defaultProposalFlag()){
                		self.changeDefaultProposal();
                	}
                });
                return this;
            },

            /**
             * Init Proposals Data
             */
            initProposals: function(proposalsData){
            	var self = this;
                var proposals = [];
                proposalsData.each(function(proposal){
                	proposal.origin_price = proposal.origin_price * 1;
                	proposal.origin_qty = proposal.qty * 1;
                	proposal.proposal_id = ko.observable(proposal.proposal_id);
                	proposal.price = ko.observable(proposal.price);
                	proposal.qty = ko.observable(proposal.origin_qty);
                	proposal.isSaved = ko.observable(true);
                	proposal.is_default = ko.observable(proposal.is_default == '1');
                	if(proposal.is_default()){
                		self.defaultProposal(proposal.proposal_id());
                	}
                	proposals.push(proposal);
                });
                this.proposals(proposals);
            },
            
            /**
             * Get Item Id
             */
            getItemId: function() {
                return this.itemId;
            },

            /**
             * Init Observable
             */
            initObservable: function () {
                return this._super()
                    .observe([
                        'proposals',
                        'isRendered',
                        'defaultProposal',
                        'defaultProposalFlag',
                        'isEditable'
                    ]);
            },

            /**
             * Set is rendered
             */
            setIsRendered: function(){
            	this.isRendered(true);
            },

            /**
             * Margin Gross Price Template Column
             * @returns {string}
             */
            getMarginGp: function (proposal) {
            	var margin = proposal.price()  - this.itemData.origin_price;
            	margin = Math.round(margin * 100 / this.itemData.origin_price,2);
            	margin = (margin > 0?'+':'') + margin * 1 + '%';
                return margin;
            },

            /**
             * Add Proposal
             * 
             * @returns {exports}
             */
            addProposal: function (element) {
            	var proposals = this.proposals();
				console.log(this.itemData);
            	proposals.push({
            		proposal_id: ko.observable(0),
                    item_id: this.itemId,
                    origin_qty: 1,
                    qty: ko.observable(1),
                    origin_price: this.itemData.base_origin_price * 1,
                    price: ko.observable(this.itemData.origin_price * 1),
                    isSaved: ko.observable(false),
                    is_default: ko.observable(false),
                });
            	
            	this.proposals(proposals)
            },

            /**
             * Remove Proposal
             * @param proposal
             */
            removeProposal: function (index) {
                var proposals = this.proposals();
                var proposal = proposals[index()];
                if(!proposal.proposal_id()){
                	proposals.splice(index(),1);
                	this.proposals(proposals);
                }else{
                	this.doRemoveProposal(proposal, index());
                }
            },
            
            /**
             * Do Remove Proposal
             */
            doRemoveProposal: function(proposal, index){
            	var self = this;
            	this._callAjax(
        			this.removeProposalUrl,
        			{proposal_id: proposal.proposal_id()},
        			function(response){
        				if(response.error === true){
                    		alert({
    		            		title: $t('Error'),
    		                    content: response.message
    		                });
                    	}else if(response.error === false){
                    		var proposals = self.proposals();
                    		proposals.splice(index,1);
                    		self.proposals(proposals);
                    	}else{
                    		alert({
    		            		title: $t('Error'),
    		                    content: response
    		                });
                    	}
        			}
    			);
            },
            
            /**
             * Update is Saved of a Proposal
             */
            updateIsSaved: function(index, type){
            	var proposals = this.proposals();
            	var proposal = proposals[index()];
            	
            	var isSaved = proposal.proposal_id() && (proposal['origin_'+ type] == proposal[type]());
            	
            	proposals[index()].isSaved(isSaved);
            	
            	this.proposals(proposals);
            },
            
            /**
             * Can save all proposals
             */
            canSaveAllProposals: function(){
            	var canSave = false;
            	this.proposals().each(function(proposal){
            		if(!proposal.isSaved()) canSave = true;
            	});
            	
            	return canSave;
            },
            
            /**
             * Save All Proposals
             */
            saveAllProposals: function(){
            	var self = this;
            	this.proposals().each(function(proposal, index){
            		if(!proposal.isSaved()){
            			self.saveProposal(ko.observable(index));
            		}
            	});
            },
            
            /**
             * Save Proposal
             * @param proposal
             */
            saveProposal:function (index) {
            	var proposals = this.proposals();
            	var proposal = proposals[index()];
            	this.doSaveProposal(proposal, index());
            },
            
            /**
             * Do Saving proposal
             */
            doSaveProposal: function(proposal, index){
            	var self = this;
            	this._callAjax(
        			this.saveProposalUrl,
        			{
                    	proposal_id: proposal.proposal_id(),
                    	item_id: proposal.item_id,
                    	price: proposal.price(),
                    	base_price: proposal.price(),
                    	qty: proposal.qty(),
                    	is_default: proposal.is_default(),
                	},
        			function(response){
                		if(response.error === true){
                    		alert({
    		            		title: $t('Error'),
    		                    content: response.message
    		                });
                    	}else if(response.error === false){
                    		var proposals = self.proposals();
                    		proposals[index].isSaved(true);
                    		proposals[index].proposal_id(response.proposal_id);
                    		self.proposals(proposals);
                    	}else{
                    		alert({
    		            		title: $t('Error'),
    		                    content: response
    		                });
                    	}
        			}
    			);
            },
            
            /**
             * Change default proposal
             */
            changeDefaultProposal: function(){
            	var self = this;
            	var proposals = this.proposals();
            	
            	/*Send ajax request to update default proposal.*/
            	this._callAjax(
        			this.saveDefaultProposalUrl,
        			{proposal_id: this.defaultProposal(), item_id: this.getItemId()},
        			function(response){
        				if(response.error === true){
                    		alert({
    		            		title: $t('Error'),
    		                    content: response.message
    		                });
                    		self.defaultProposalFlag(false);
                    		self.resetDefaultProposal();
                    		self.defaultProposalFlag(true);
                    	}else if(response.error === false){
                    		proposals.each(function(proposal, index){
                        		if(proposal.proposal_id() == self.defaultProposal()){
                        			proposals[index].is_default(true);
                        		}else{
                        			proposals[index].is_default(false);
                        		}
                        	});
                    	}else{
                    		alert({
    		            		title: $t('Error'),
    		                    content: response
    		                });
                    		self.defaultProposalFlag(false);
                    		self.resetDefaultProposal();
                    		self.defaultProposalFlag(true);
                    	}
        			}
    			);
            },
            
            /**
             * Reset default proposal. 
             * This method is called when changing default proposal does not successfully.
             */
            resetDefaultProposal: function(){
            	var self = this;
            	this.proposals().each(function(proposal){
            		if(proposal.is_default()){
            			self.defaultProposal(proposal.proposal_id());
            		}
            	});
            },

            /**
             * Call AJAX
             * @param url
             * @param params
             * @private
             */
            _callAjax: function (url, params, callBack) {
                $.ajax({
                    url: url,
                    method: "POST",
                    data: params,
                    showLoader: true,
                    dataType: "json"
                }).done(function (response) {
                	if(callBack) callBack(response);
                }).fail(function () {
                	alert({
	            		title: $t('Error'),
	                    content: $t('Something wrong. Please try to refresh the page.')
	                });
                });
            }
        });
    }
);
