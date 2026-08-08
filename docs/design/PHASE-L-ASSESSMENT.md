# Phase L — MGS decommission: assessment

**Verdict: Phase L cannot execute, and should not be attempted yet.**

Not a scheduling problem — a correctness one. Everything below is measured.

## The blocker

```
core_config_data → design/theme/theme_id @ default/0  =  7  =  Mgs/supro
                   design/theme/ua_regexp             =  [{"regexp":"/HubMarketPreview/","value":"11"}]
```

**`Mgs/supro` is still the live theme for all real traffic.** The Hub Market theme
is reachable only by sending a User-Agent containing `HubMarketPreview`. Every MGS
module is enabled, and the live storefront renders **112 MGS tokens** per page.

Disabling MGS modules today breaks the live shop. Phase L is gated on a go-live
decision that has not been made — and that is your call, not mine.

## What is actually at stake

Measured today, not estimated:

| Table | Rows | |
|---|---|---|
| `mgs_brand` / `mgs_brand_product` / `mgs_brand_store` | 29 / **190** / 29 | real brand catalogue |
| `mgs_fbuilder_section` / `_child` / `_confirm` | 89 / 148 / 17 | drives CMS page content |
| `mgs_megamenu` / `_store` / `_cache` / `_parent` | 27 / 27 / 49 / 1 | navigation |
| `mgs_blog_post` / `_post_store` / `_category` / `_category_post` | 3 / 7 / 1 / 3 | small but real |
| `mgs_protabs` | 3 | |
| `mgs_lookbook` | 1 | |
| `mgs_portfolio_*` (4 tables) | **0** | empty — safe |
| `mgs_blog_comment`, `mgs_blog_tag`, `mgs_lookbook_slide*` | **0** | empty |

## Two findings that make this much less scary than the raw numbers suggest

### 1. Sixteen of the eighteen "MGS CMS pages" are demo content

The 18 `cms_page` rows containing MGS markup are almost all retired Supro demos:

```
home-bestselling  home-boxed      home-carousel   home-classic   home-default
home-fullslider   home-fullwidth  home-instagram  home-leftsidebar
home-masonry      home-metro      home-minimal    home-modern
home-parallax     home-shoppable  home-simple
```

Only `home` (page_id 2 and 84) is a real page. And under the Hub Market theme it
does not matter: the theme-scoped `Magento_Cms/layout/cms_index_index.xml` removes
`cms_page` and renders the `hm_home_*` blocks instead — verified, 19 Hub Market home
blocks present on both `/en/` and `/ar/`.

You asked earlier that CMS demo content be left alone. That still holds — but note
those 16 pages will render empty once Fbuilder is gone. They are already
unreachable from any navigation.

### 2. The two "MGS" CMS blocks are false positives

`supro_footer_6_top_block_1` and `whatsapp_widget` matched only because they
reference a **media path**, `wysiwyg/fbuilder/logo1.png` — not a widget. The images
live in `pub/media` and keep resolving after the module is gone. `whatsapp_widget`
is real, live functionality and is unaffected.

## The new theme has no hard dependency on MGS

Every `MGS_` reference in `app/design/frontend/MagentoEgypt/hub-market` is either a
comment or a removal:

```
<remove src="MGS_ThemeSettings::css/theme_setting.css"/>
<remove src="MGS_Mmegamenu::css/megamenu.css"/>
<remove src="MGS_Brand::css/mgs_brand.css"/>
```

plus defensive styling in `_hm-thirdparty.less` for the `MGS_AjaxCart` floating cart
and the `MGS_Amp` scroll-to-top. Those `<remove>` directives become harmless no-ops
once the modules are disabled.

**One real coupling:** `MGS_Amp` replaces Magento's `Topmenu` via a plugin. The Hub
Market `topmenu.phtml` instantiates the catalog plugin manually because of it. That
needs re-testing when `MGS_Amp` goes, not assuming.

**One module needs care regardless of theme: `MGS_GDPR`.** It is a compliance
component, not decoration. Nothing in the Hub Market theme replaces it. It should
not be in any decommission wave until someone confirms what obligations it carries
and what replaces them.

## Cost per toggle

Enabling `MagentoEgypt_SellerTheme` in Phase K proved that on this install
`bin/magento module:enable` clears `generated/` — including `generated/metadata` —
so **every module toggle implies a `setup:di:compile` (~3 minutes)** in production
mode. A 20-module decommission is therefore a small number of batched waves with a
compile per wave, not twenty individual toggles.

## The sequence, when it is unblocked

Preconditions, all of them:

1. Hub Market is live (`design/theme/theme_id` = 11 at `default/0`), and the
   `ua_regexp` preview row is removed.
2. A database dump exists and has been verified by row count.
3. Brand data has a destination — 29 brands and 190 product links have **no Hub
   Market equivalent today**. Either build one or accept the loss, explicitly.
4. Megamenu content is confirmed redundant. The Hub Market nav is built from
   Magento's own category tree, so the 27 `mgs_megamenu` rows are probably
   superfluous — but confirm against the live menu before deleting.

Then, safest first:

| Wave | Modules | Why it is safe |
|---|---|---|
| 1 | `MGS_Portfolio` | all four tables empty, nothing references it |
| 2 | `MGS_Lookbook`, `MGS_Testimonial`, `MGS_ExportBlock` | 1 row or none; only `home-shoppable` (a demo page) uses Lookbook |
| 3 | `MGS_ThemeSettings`, `MGS_SuproTheme`, `MGS_Mmegamenu` | pure Supro chrome; the new theme already removes their CSS |
| 4 | `MGS_Fbuilder` | 89 sections, but only demo pages consume them under the new theme |
| 5 | `MGS_AjaxCart`, `MGS_Aquickview`, `MGS_Ajaxlayernavigation`, `MGS_InstantSearch`, `MGS_Guestwishlist`, `MGS_ExtraGallery`, `MGS_Protabs`, `MGS_Amp` | behavioural — each needs a "does core cover this?" decision, and `MGS_Amp` needs the Topmenu retest |
| 6 | `MGS_Brand`, `MGS_Blog` | **data migration first**, or accept losing 29 brands / 190 links / 3 posts |
| — | `MGS_GDPR` | **do not remove.** Compliance function, no replacement. |

Verification after each wave should be specific: `/en/`, `/ar/`, a category page, a
PDP, cart, checkout, and the seller directory — checking HTTP status, stylesheet
count, and that no new `Invalid template file` lines appear in `var/log/system.log`.

Rollback per wave: re-enable the modules, `setup:di:compile`, redeploy static,
flush cache. Budget ~10 minutes.

## Why this document is an assessment and not an execution

I ran a workflow to classify all 20 modules in depth. **It took the site down and I
killed it.** Three agents running concurrently on a 2-core box drove load average to
**28.7**; Apache is configured with `MaxRequestWorkers 24` and the php-fpm pools on
this shared host allow only 8–12 children each, so the worker pool was exhausted and
`proxy_fcgi` began returning `AH01075` dispatch timeouts. MySQL and OpenSearch both
restarted under the pressure (12:19 and 12:28).

That is my error, and it is the second time parallel agents have overwhelmed this
box. The operational lesson is concrete and belongs in the record: **this host
cannot take concurrent agent fan-out.** Any future analysis of this kind has to run
sequentially, or off a database copy rather than the live server.

The classification above is therefore built from the direct measurements in this
document rather than from the workflow, which never returned.
