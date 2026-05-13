require([
    'jquery'
], function ($) {
    'use strict';

    function byId(id)
    {
        return $('#' + id);
    }

    function onUrlkeyChanged(urlKey)
    {
        urlKey = byId(urlKey);
        var hidden = urlKey.siblings('input[type=hidden]');
        var chbx = urlKey.siblings('input[type=checkbox]');
        var oldValue = chbx.val();

        chbx.prop('disabled', oldValue === urlKey.val());
        hidden.prop('disabled', chbx.prop('disabled'));
    }

    window.onUrlkeyChanged = onUrlkeyChanged;
});