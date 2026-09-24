/**
 * Hub Market override of Algolia_AlgoliaSearch/js/template/recommend/products.js
 * (DEV07 Recommendations).
 *
 * Upstream renders an image and a name in its own markup. These cards emit the
 * SAME structure as the theme's product rails (Magento_CatalogWidget grid.phtml:
 * .product-item-info > .product-item-media / .product-item-details, seller line,
 * price, icon Add to Cart), so _hm-product-item.less and the rail grid rules
 * style them with no new CSS, in LTR and RTL alike. The recommend root carries
 * `block widget` (see js/recommend.js classNames) for the same reason.
 *
 * Every link carries data-objectid / data-position / data-queryid for the
 * "Recommended Product Clicked" event, and ?queryID= so an Add to Cart on the
 * product page is attributed to this recommendation (conversion).
 */
define(['algoliaCommon', 'algoliaBase64'], function (algoliaCommon, algoliaBase64) {
    'use strict';

    function withQueryId(url, queryID) {
        if (!queryID) {
            return url;
        }

        try {
            var u = new URL(url, window.location.href);

            u.searchParams.set('queryID', queryID);

            return u.toString();
        } catch (e) {
            return url;
        }
    }

    return {
        getItemHtml: function (item, html, addTocart) {
            var cfg = window.algoliaConfig;
            var params = cfg.recommend.addToCartParams;
            var formKey = algoliaCommon.getCookie('form_key');
            var action = params.action + 'product/' + item.objectID + '/';
            var prices = (item.price && item.price[cfg.currencyCode]) || {};
            var group = cfg.priceGroup || 'default';
            var price = prices[group + '_formated'];
            var original = prices[group + '_original_formated'];
            var index = cfg.indexName + '_products';
            var url = withQueryId(item.url, item.__queryID);

            if (formKey && params.formKey !== formKey) {
                params.formKey = formKey;
            }

            return html`<div class="product-item-info hm-rec__card">
                <div class="product-item-media">
                    <a class="product-item-photo hm-rec__link" href=${url}
                       data-objectid=${item.objectID} data-position=${item.position}
                       data-queryid=${item.__queryID || ''} data-index=${index}>
                        <span class="product-image-container">
                            <span class="product-image-wrapper">
                                <img class="product-image-photo" src=${item.image_url} alt=${item.name} loading="lazy"/>
                            </span>
                        </span>
                    </a>
                </div>
                <div class="product-item-details">
                    ${item.seller ? html`<span class="hm-card__vendor">${item.seller}</span>` : ''}
                    <strong class="product-item-name">
                        <a class="product-item-link hm-rec__link" href=${url} title=${item.name}
                           data-objectid=${item.objectID} data-position=${item.position}
                           data-queryid=${item.__queryID || ''} data-index=${index}>${item.name}</a>
                    </strong>
                    <div class="hm-card__foot">
                        ${price ? html`<div class="price-box price-final_price">
                            <span class="price-container"><span class="price-wrapper"><span class="price">${price}</span></span></span>
                            ${original ? html`<span class="old-price"><span class="price">${original}</span></span>` : ''}
                        </div>` : ''}
                        ${addTocart ? html`<div class="product-item-inner"><div class="product-item-actions"><div class="actions-primary">
                            <form class="addTocartForm" action=${action} method="post" data-role="tocart-form">
                                <input type="hidden" name="form_key" value=${params.formKey} />
                                <input type="hidden" name="unec" value=${algoliaBase64.mageEncode(action)} />
                                <input type="hidden" name="product" value=${item.objectID} />
                                ${item.__queryID ? html`<input type="hidden" name="queryID" value=${item.__queryID} />` : ''}
                                <button type="submit" class="action tocart primary" title=${cfg.translations.addToCart}
                                        data-queryid=${item.__queryID || ''}>
                                    <svg class="hm-icon hm-icon--sm" aria-hidden="true" focusable="false"><use href="#hm-cart"/></svg>
                                    <span>${cfg.translations.addToCart}</span>
                                </button>
                            </form>
                        </div></div></div>` : ''}
                    </div>
                </div>
            </div>`;
        },

        getHeaderHtml: function (html, title) {
            return html`<div class="block-title hm-section__head">
                <div class="hm-section__heading"><h2>${title}</h2></div>
            </div>`;
        }
    };
});
