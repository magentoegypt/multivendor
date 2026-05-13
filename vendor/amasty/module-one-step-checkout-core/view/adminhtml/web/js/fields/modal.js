define([
    'jquery',
    'text!Amasty_CheckoutCore/template/fields/modal-popup.html',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, popupTpl, modal, $t) {
    'use strict';

    $.widget('mage.modalFields', $.mage.modal, {
        options: {
            responsive: true,
            title: $t('Custom Fields'),
            trigger: '#custom-fields-button',
            popupTpl: popupTpl,
            modalClass: 'amcheckout-custom-fields',
            buttons: [],
            tooltip: $t('Please take note that custom fields will also be displayed in the Address Book,' +
                ' and their management is limited to the default value level.')
        },
    });

    return $.mage.modalFields;
});
