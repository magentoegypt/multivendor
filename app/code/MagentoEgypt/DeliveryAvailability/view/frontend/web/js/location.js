define(['jquery', 'mage/url', 'mage/translate'], function ($, url, $t) {
    'use strict';
    return function (config, element) {
        var root=$(element), country=root.find('[data-role=country]'), region=root.find('[data-role=region]'),
            city=root.find('[data-role=city]'), locality=root.find('[data-role=locality]'),
            summary=root.find('[data-role=summary]'), save=root.find('[data-role=save]'), generation=0,
            selection=null, key='hub_delivery_area_v1', arabic=document.documentElement.lang.indexOf('ar')===0;
        function reset(select) { select.empty().append($('<option>').val('').text($t('Select'))); }
        function message(status) {
            return $t({green:'Area covered. Stock and fees are checked at checkout.', red:'This area requires a delivery quotation.',
                blacklist:'Delivery unavailable in this area.', unconfigured:'Coverage not confirmed yet. Check at checkout.'}[status] || 'Delivery check unavailable.');
        }
        function check(value) {
            var expected=value;
            summary.text($t('Checking delivery area...'));
            $.getJSON(url.build('deliveryavailability/check/index'), value).done(function (result) {
                if (selection !== expected) return;
                summary.text(value.label+' — '+message(result.coverage));
            }).fail(function () { if(selection===expected) summary.text($t('Delivery check unavailable. Please choose the area again.')); });
        }
        function load(select, level, parent, done) {
            var current=++generation;
            save.prop('disabled',true); reset(select);
            $.getJSON(url.build('citymanager/directory/index'), {country:country.val(),level:level,parent:parent||0,region:region.val()||0})
                .done(function (response) {
                    if(current!==generation) return;
                    response.items.forEach(function(item) {
                        var value=level==='region'?item.region_id:item.location_id;
                        select.append($('<option>').val(value).text(arabic&&item.name_ar?item.name_ar:item.name_en).attr('data-region',item.region_id));
                    });
                    if(done) done(response.items);
                }).fail(function () { if(current===generation) summary.text($t('Location service unavailable. Please choose the country again.')); });
        }
        function start() {
            reset(region); reset(city); reset(locality);
            root.find('[data-role=region-label]').prop('hidden',country.val()==='AE');
            root.find('[data-role=locality-label]').prop('hidden',true);
            if(country.val()==='AE') load(city,'city'); else load(region,'region');
        }
        country.on('change',start);
        region.on('change',function () { reset(locality); load(city,'city'); });
        city.on('change',function () {
            ++generation;
            reset(locality); save.prop('disabled',true);
            if(!city.val()) return;
            if(country.val()==='AE') load(locality,'locality',city.val(),function(items) {
                root.find('[data-role=locality-label]').prop('hidden',!items.length);
                save.prop('disabled',!!items.length);
            }); else save.prop('disabled',false);
        });
        locality.on('change',function () { save.prop('disabled',!locality.val()); });
        root.find('[data-role=toggle]').on('click',function () {
            var picker=root.find('[data-role=picker]'); picker.prop('hidden',!picker.prop('hidden'));
            if(!picker.prop('hidden')) start();
        });
        save.on('click',function () {
            selection={country:country.val(),region:country.val()==='AE'?city.find(':selected').attr('data-region'):region.val(),
                city:city.val(),locality:locality.val()||0,label:city.find(':selected').text()+(locality.val()?' / '+locality.find(':selected').text():'')};
            try { sessionStorage.setItem(key,JSON.stringify(selection)); } catch(ignore) {}
            root.find('[data-role=picker]').prop('hidden',true); check(selection);
        });
        root.find('[data-role=clear]').on('click',function () {
            selection=null; try {sessionStorage.removeItem(key);} catch(ignore) {}
            summary.text(''); root.find('[data-role=picker]').prop('hidden',true);
        });
        try { selection=JSON.parse(sessionStorage.getItem(key)); if(selection) check(selection); } catch(ignore) {}
    };
});
