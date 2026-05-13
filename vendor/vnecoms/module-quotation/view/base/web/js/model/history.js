/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define(
    ['ko', 'moment'],
    function (ko, moment) {
        'use strict';

        var messages = ko.observableArray();

        return {
            messages: messages,

            sortItems: function(itemOne, itemTwo) {
                return itemOne.created_at < itemTwo.created_at ? 1 : -1
            },
            
            addMessage: function (message, author, name, created_at, attachment='') {
                messages.push({
                    message: message,
                    author: author,
                    name: name,
                    created_at: created_at,
                    attachment: attachment
                });
            }
        }
    });