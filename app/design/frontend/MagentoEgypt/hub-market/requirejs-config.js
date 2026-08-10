/**
 * Hub Market theme RequireJS map.
 *
 * Theme-level JS lives at web/js/ and is published to
 * pub/static/frontend/MagentoEgypt/hub-market/<locale>/js/, which is directly
 * under RequireJS's baseUrl — so the target is a plain `js/<name>` path with no
 * module namespace in front of it. The alias exists so templates can say
 * `{"hmCartQty": {}}` in data-mage-init instead of hard-coding that path.
 */
var config = {
    map: {
        '*': {
            hmCartQty: 'js/hm-cart-qty'
        }
    }
};
