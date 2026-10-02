define(['jquery', 'mage/translate', 'ko'], function ($, $t, ko) {
    'use strict';
    return function (config) {
        if (window.meCityManagerStarted) { return; }
        window.meCityManagerStarted = true;
        var originalTranslate=$t, arabic=(document.documentElement.lang||'').indexOf('ar')===0;
        var translations={'City':'المدينة','Locality':'الحي','Governorate':'المحافظة','Region':'المنطقة','State':'الولاية','Select city':'اختر المدينة','Select locality':'اختر الحي','No localities configured':'الحي اختياري لهذه المدينة','Retry':'إعادة المحاولة','Locations could not load. Please retry.':'تعذر تحميل المواقع. يرجى إعادة المحاولة.','Select a valid city and locality where required.':'اختر مدينة صحيحة والحي عندما يكون مطلوباً.','Please select a city to update this saved address.':'اختر المدينة لتحديث هذا العنوان.'};
        $t=function(text){return arabic && translations[text] ? translations[text] : originalTranslate(text);};
        var cache = {}, sequence = 0;
        function options(params) {
            var key = $.param(params);
            if (!cache[key]) {
                cache[key] = $.ajax({url:config.endpoint,type:'GET',data:params,dataType:'json',timeout:15000}).then(function (r) { return r.items; });
                cache[key].fail(function () { delete cache[key]; });
            }
            return cache[key];
        }
        function field(scope, name) {
            return $(scope).find('input,select').filter(function () {
                return this.name === name || this.name.endsWith('[' + name + ']');
            }).not('[data-cm-control]').first();
        }
        function value(el, v) {
            if (!el.length || String(el.val()) === String(v)) { return; }
            el.val(v).trigger('input').trigger('change');
        }
        function wrapper(el) { return el.closest('.field,.admin__field'); }
        function label(select, title) {
            var parent = wrapper(select);
            parent.find('label').first().find('span').first().text($t(title));
        }
        function install(input) {
            var city = $(input), scope = city.closest('fieldset,.fieldset,.admin__fieldset');
            if (!scope.length || !field(scope, 'country_id').length) { scope = city.closest('form'); }
            if (!scope.length) { return; }
            var country = field(scope, 'country_id'), region = field(scope, 'region_id'), regionText = field(scope, 'region');
            if (!country.length) { return; }
            var identifiers = {};
            ['cm_city_id','cm_locality_id'].forEach(function (key) {
                identifiers[key] = field(scope,key);
                if (identifiers[key].length) { wrapper(identifiers[key]).hide(); }
                if (!identifiers[key].length) identifiers[key]=$('<input type="hidden"/>').attr('name',city.attr('name').replace(/city(\]?)$/,key+'$1')).insertAfter(city);
            });
            function writeIds(cityId,localityId) {
                var values={cm_city_id:cityId||'',cm_locality_id:localityId||''};
                Object.keys(values).forEach(function(key) {
                    identifiers[key].val(values[key]);
                    var context=ko.contextFor(input), component=context && context.$data;
                    if(component && component.source && component.dataScope && /\.city$/.test(component.dataScope)) {
                        component.source.set(component.dataScope.replace(/\.city$/,'.custom_attributes.'+key),values[key]);
                    }
                });
            }
            city.attr('data-cm-bound','1');
            var id = 'cm-location-' + (++sequence), select = $('<select class="select admin__control-select" data-cm-control="city"/>').attr({'id':id,'aria-label':$t('City')}),
                areaField = $('<div class="field required cm-locality-field"><label class="label"><span></span></label><div class="control"></div></div>'),
                locality = $('<select class="select admin__control-select" data-cm-control="locality"/>').attr({'id':id+'-locality','aria-label':$t('Locality')}),
                error = $('<div role="alert" class="mage-error cm-error"/>').hide(), ticket = 0, records = [], localRecords = [], ready = false;
            select.insertAfter(city); error.insertAfter(select);
            var retry=$('<button type="button" class="action cm-retry"/>').text($t('Retry')).insertAfter(error).hide().on('click',function(){refresh(true);});
            areaField.find('label').attr('for',id+'-locality').find('span').text($t('Locality'));
            areaField.find('.control').append(locality);
            areaField.insertAfter(wrapper(city).length ? wrapper(city) : select).hide();
            function fill(el, rows, placeholder) {
                el.empty().append($('<option/>').val('').text($t(placeholder)));
                var arabic = (document.documentElement.lang || '').indexOf('ar') === 0;
                rows.forEach(function (r) { el.append($('<option/>').val(r.location_id).text(arabic && r.name_ar ? r.name_ar : r.name_en)); });
            }
            function changed() {
                var chosen = records.find(function (r) { return String(r.location_id) === select.val(); }),
                    local = localRecords.find(function (r) { return String(r.location_id) === locality.val(); });
                if (!chosen) { writeIds('',''); value(city,''); return; }
                writeIds(chosen.location_id,local && local.location_id);
                if (country.val() === 'AE') {
                    if (!region.find('option[value="'+chosen.region_id+'"]').length) {
                        region.append($('<option/>').val(chosen.region_id).text(chosen.name_en));
                    }
                    value(region,chosen.region_id);
                }
                value(city,chosen.name_en + (country.val()==='AE' && local ? ' / '+local.name_en : ''));
            }
            function loadLocality(saved) {
                ready=false;
                var currentTicket=++ticket;
                localRecords=[]; fill(locality,[],'Select locality'); locality.prop('disabled',true);
                if (!saved) { writeIds('',''); value(city,''); }
                if(!select.val()) { changed(); return; }
                options({country:'AE',level:'locality',parent:select.val()}).done(function(rows) {
                    if(currentTicket!==ticket) return;
                    ready=true; localRecords=rows; fill(locality,rows,rows.length?'Select locality':'No localities configured');
                    locality.prop('disabled',!rows.length).prop('required',rows.length>0);
                    areaField.toggleClass('required',rows.length>0);
                    var match=rows.find(function(r){return r.name_en===saved || r.name_ar===saved;});
                    if(match) locality.val(match.location_id);
                    changed();
                }).fail(function(){ if(currentTicket===ticket) { error.text($t('Locations could not load. Please retry.')).show(); retry.show(); } });
            }
            function refresh(preserve) {
                var c=country.val(), supported=config.countries.indexOf(c)>=0, currentTicket=++ticket, saved=preserve?String(city.val()||'').split(' / '):[];
                ready=false;
                error.hide(); retry.hide(); city.toggle(!supported); select.toggle(supported).prop('required',supported);
                areaField.toggle(c==='AE'); locality.prop('required',c==='AE');
                if (supported) {
                    wrapper(region).toggle(c!=='AE');
                    wrapper(regionText).hide();
                }
                if(!supported) { wrapper(region).show(); wrapper(regionText).show(); writeIds('',''); return; }
                if(c!=='AE') label(region,{EG:'Governorate',SA:'Region',US:'State'}[c]);
                records=[]; fill(select,[],'Select city'); select.prop('disabled',true);
                if(!preserve) { writeIds('',''); value(city,''); }
                function loadCities() {
                    if(currentTicket!==ticket) return;
                    options({country:c,level:'city',region:c==='AE'?0:region.val()}).done(function(rows){
                        if(currentTicket!==ticket) return;
                        ready=c!=='AE'; records=rows; fill(select,rows,'Select city'); select.prop('disabled',false);
                        var savedId=preserve?identifiers.cm_city_id.val():'';
                        var matches=rows.filter(function(r){return savedId?String(r.location_id)===String(savedId):r.name_en===saved[0] || r.name_ar===saved[0];});
                        if(matches.length===1) select.val(matches[0].location_id);
                        if(c==='AE') loadLocality(saved[1]);
                        else if(matches.length===1) changed();
                        else if(saved[0]) error.text($t('Please select a city to update this saved address.')).show();
                    }).fail(function(){ if(currentTicket===ticket) { error.text($t('Locations could not load. Please retry.')).show(); retry.show(); } });
                }
                if(c==='AE') { loadCities(); return; }
                if(!region.length) { error.text($t('Region selector unavailable on this form.')).show(); return; }
                options({country:c,level:'region'}).done(function(rows){
                    if(currentTicket!==ticket) return;
                    // Core regionUpdater may not yet have refreshed a classic form.
                    rows.forEach(function(r){if(!region.find('option[value="'+r.region_id+'"]').length) region.append($('<option/>').val(r.region_id).text(r.name_en));});
                    region.show(); wrapper(region).show();
                    if(region.val()) loadCities();
                }).fail(function(){if(currentTicket===ticket){error.text($t('Locations could not load. Please retry.')).show();retry.show();}});
            }
            country.on('change.cm',function(){refresh(false);});
            region.on('change.cm',function(){if(country.val()!=='AE') refresh(false);});
            select.on('change',function(){error.hide();if(country.val()==='AE') loadLocality('');else changed();});
            locality.on('change',changed);
            city.closest('form').on('submit.cm'+sequence,function(event){
                if(config.countries.indexOf(country.val())<0) return;
                if(!ready || !select.val() || (country.val()==='AE'&&localRecords.length>0&&!locality.val())) {
                    event.preventDefault();event.stopImmediatePropagation();error.text($t('Select a valid city and locality where required.')).show();select.trigger('focus');
                }
            });
            refresh(true);
        }
        function scan() {
            $('input[name="city"],input[name$="[city]"]').not('[data-cm-bound]').each(function(){install(this);});
        }
        var timer;
        new MutationObserver(function(){clearTimeout(timer);timer=setTimeout(scan,100);}).observe(document.body,{childList:true,subtree:true});
        scan();
    };
});
