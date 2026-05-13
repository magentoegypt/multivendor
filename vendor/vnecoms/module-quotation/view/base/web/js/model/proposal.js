/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define(
    ['ko', 'moment', 'underscore'],
    function (ko, moment, _) {
        'use strict';

        var proposals = ko.observableArray();

        return {
            proposals: proposals,

            addItem: function (item_id = '', qty='', price='',isDefault=false) {
                proposals.push({
                    item_id: item_id,
                    qty: qty,
                    price: price,
                    isDefault: isDefault
                });
            },

            removeItem: function (proposal) {
                proposals.remove(proposal);
            }
        }
    });