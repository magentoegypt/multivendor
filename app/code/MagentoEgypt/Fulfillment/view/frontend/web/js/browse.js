define(['jquery'],function($){
    'use strict';
    return function(){
        var key='hub_delivery_area_v1', fields=['country','region','city','locality'];
        function selection(){try{return JSON.parse(sessionStorage.getItem(key));}catch(ignore){return null;}}
        function eligible(url){return url.origin===location.origin && !/\/(checkout|customer|sales|vendors|admin|rest|graphql|citymanager|deliveryavailability|hubfulfillment)(\/|$)/.test(url.pathname);}
        function scoped(url,area){fields.forEach(function(field){if(area)url.searchParams.set('hf_'+field,area[field]||0);else url.searchParams.delete('hf_'+field);});return url;}
        function reload(){var url=new URL(location.href);if(eligible(url)){url=scoped(url,selection());if(url.href!==location.href)location.assign(url.href);}}
        // Register after the existing picker; its handler first commits the selection to session storage.
        $(document).on('click','#delivery-area [data-role=save],#delivery-area [data-role=clear]',function(){setTimeout(reload,0);});
        $(document).on('click','a[href]',function(){
            var url;try{url=new URL(this.href,location.href);}catch(ignore){return;}
            if(eligible(url) && !this.hasAttribute('download') && !url.hash) this.href=scoped(url,selection()).href;
        });
        $(document).on('submit','form',function(){
            if((this.method||'get').toLowerCase()!=='get')return;
            var url=new URL(this.action||location.href,location.href);if(!eligible(url))return;
            var area=selection(), form=$(this); form.find('[data-hf-area]').remove();
            if(area)fields.forEach(function(field){form.append($('<input type="hidden" data-hf-area="1">').attr('name','hf_'+field).val(area[field]||0));});
        });
        reload();
    };
});
