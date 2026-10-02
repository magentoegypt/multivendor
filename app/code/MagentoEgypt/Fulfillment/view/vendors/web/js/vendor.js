define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';
    return function (config, element) {
        var root=$(element), products=[], directory=new URL('/citymanager/directory/index', window.location.origin).href;
        function select(label, values) {
            var field=$('<select>'); field.append($('<option>').val('').text($t('Select')));
            (values||[]).forEach(function(v){field.append($('<option>').val(v[0]).text(v[1]));});
            return {field:field,label:$('<label>').text($t(label)+' ').append(field)};
        }
        function add(product) {
            var box=$('<fieldset>'), sku=$('<input>').val(product.sku||''), modes={}, coverage=product.coverage,
                list=$('<div>'), country=select('Country',[['EG','Egypt'],['SA','Saudi Arabia'],['AE','United Arab Emirates'],['US','United States']]),
                region=select('Region'), city=select('City'), locality=select('Locality'), generation=0,
                status=$('<p>').attr('role','status'), addArea=$('<button type="button">').text($t('Add coverage area')).prop('disabled',true),
                inherit=$('<input type="checkbox">').prop('checked',coverage===undefined);
            box.append($('<label>').text($t('Product SKU')+' ').append(sku));
            [['vendor','I ship products'],['marketplace','Marketplace collects and delivers'],['hub','Delivery through the marketplace hub']].forEach(function(pair){
                modes[pair[0]]=$('<input type="checkbox">').prop('checked',(product.modes||[]).indexOf(pair[0])!==-1);
                box.append($('<label>').append(modes[pair[0]]).append(document.createTextNode($t(pair[1]))));
            });
            var picker=$('<div>').append(country.label,region.label,city.label,locality.label,addArea,status,list);
            box.append($('<label>').append(inherit).append(document.createTextNode($t('Use all marketplace-configured routes'))),picker);
            function render() {
                picker.prop('hidden',inherit.prop('checked')); list.empty();
                (coverage||[]).forEach(function(area,index){
                    list.append($('<p>').text(area.label||[area.country,area.city_id,area.locality_id].join(' / ')).append(
                        $('<button type="button">').text($t('Remove')).on('click',function(){coverage.splice(index,1);render();})));
                });
            }
            inherit.on('change',render);
            function reset(field){field.empty().append($('<option>').val('').text($t('Select')));}
            function load(field,level,parent) {
                var token=++generation; addArea.prop('disabled',true); reset(field); status.text($t('Loading areas...'));
                return $.getJSON(directory,{country:country.field.val(),level:level,parent:parent||0,region:region.field.val()||0}).then(function(result){
                    if(token!==generation) return;
                    result.items.forEach(function(i){field.append($('<option>').val(level==='region'?i.region_id:i.location_id).text(i.name_en));});
                    status.text(''); if(level==='locality') addArea.prop('disabled',false);
                },function(){if(token===generation)status.text($t('Could not load areas. Choose the country again.'));});
            }
            country.field.on('change',function(){reset(region.field);reset(city.field);reset(locality.field);region.label.prop('hidden',country.field.val()==='AE');load(country.field.val()==='AE'?city.field:region.field,country.field.val()==='AE'?'city':'region');});
            region.field.on('change',function(){reset(locality.field);load(city.field,'city');});
            city.field.on('change',function(){load(locality.field,'locality',city.field.val());});
            addArea.on('click',function(){
                if(!city.field.val())return;
                coverage=coverage||[]; coverage.push({country:country.field.val(),city_id:Number(city.field.val()),locality_id:Number(locality.field.val()||0),label:city.field.find(':selected').text()+(locality.field.val()?' / '+locality.field.find(':selected').text():'')});render();
            });
            var record={read:function(){var result={sku:sku.val().trim(),modes:Object.keys(modes).filter(function(m){return modes[m].prop('checked');})};
                if(!inherit.prop('checked'))result.coverage=(coverage||[]).map(function(a){return {country:a.country,city_id:a.city_id,locality_id:a.locality_id};});return result;}};
            box.append($('<button type="button">').text($t('Remove override')).on('click',function(){products.splice(products.indexOf(record),1);box.remove();}));
            products.push(record);root.find('[data-role=products]').append(box);render();
        }
        (config.products||[]).forEach(add);
        root.find('[data-role=add]').on('click',function(){add({});});
        root.find('[data-role=policy]').on('submit',function(){
            var modes=[];root.find('[data-mode]:checked').each(function(){modes.push($(this).attr('data-mode'));});
            root.find('[name=policy_json]').val(JSON.stringify({modes:modes,products:products.map(function(p){return p.read();})}));
        });
    };
});
