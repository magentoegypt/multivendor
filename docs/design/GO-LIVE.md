# Go-live — MagentoEgypt/hub-market

**2026-08-06 13:19 UTC.** `core_config_data → design/theme/theme_id @ default/0`
changed **7 (Mgs/supro) → 11 (MagentoEgypt/hub-market)**. There was exactly one
such row, so no store-scoped overrides had to be reconciled.

## Backup

```
/home/ubuntu/db-backups/multi_vendor_m2-pre-theme-switch-20260806-131546.sql.gz   24 MB
```

Verified rather than assumed:

| Check | Result |
|---|---|
| `gzip -t` | intact |
| Trailer | `-- Dump completed on 2026-08-06 13:16:08` |
| `CREATE TABLE` in dump vs live `BASE TABLE` | **556 = 556** |
| Row counts (tuples inside the extended INSERTs) | `core_config_data` 1795=1795, `theme` 11=11, `cms_page` 81=81, `cms_block` 293=293 |
| Rollback row present | `(28,'default',0,'design/theme/theme_id','7')` |

The only `mysqldump` stderr was a tablespace `PROCESS` privilege warning, which does
not affect table data.

## Rollback

```bash
php8.4 bin/magento config:set design/theme/theme_id 7 && php8.4 bin/magento cache:flush
```

Supro's static content was never touched, so this is a complete revert. Nothing else
needs undoing — every fix below lives in the hub-market theme, which Supro ignores.
The two content corrections are DB edits and are covered by the backup above.

## Verified live — 13 URLs, both locales, plain User-Agent

All 200 (404 where expected), all on hub-market, all with a non-empty `<title>`,
**zero** `Invalid template file` lines across the whole sweep. Arabic renders
`dir=rtl` in IBM Plex Sans Arabic with no overflow and no failed stylesheets.

## Five defects found by going live, all fixed

Going live surfaced things that preview traffic never would.

### 1. Two false claims on the homepage — the most serious

The USP strip still carried the Figma's Gulf framing:

> *"Fast Gulf delivery — Same-day and next-day across UAE, KSA, Qatar, Bahrain and Kuwait."*
> *"Secure payment — Mada, Tap, Tabby, STC Pay and all major cards."*

Neither is true. Checked against the store's own configuration:

```
currency/options/base .............. EGP
payment methods actually enabled ... cashondelivery, online (Visa/Mastercard),
                                     paypal_billing_agreement
```

Mada, Tap, Tabby and STC Pay are **not configured**, and there is no Gulf shipping
setup. The footer was worse: it advertised **eight** payment methods (valU, Sympl,
Souhoola, Shahry, Aman) when **three** are enabled.

Now: *"Nationwide delivery — Shipping across Egypt, with tracking from your
account."* and *"Secure payment — Cash on delivery, Visa and Mastercard."* Footer
chips reduced to Visa / Mastercard / Cash on Delivery.

Advertising payment methods the checkout cannot honour is a promise the shop
cannot keep, so this was fixed before anything cosmetic.

### 2. The Arabic storefront was showing English

The theme shipped **no i18n at all**, so the Arabic store view rendered the English
USP strings verbatim. Added `i18n/ar_SA.csv` with 21 strings covering the USP strip,
header, nav and homepage headings.

### 3. `<title></title>` on the homepage

The homepage shipped with a literal empty title element — on the single most
important page on the site. Every other page was fine, which is why it hid.

Cause: the homepage layout removed the `cms_page` block to suppress the legacy
Fbuilder body, and `Magento\Cms\Block\Page::_prepareLayout()` is also what sets the
title, meta description, meta keywords and the `cms-*` body class.

My first fix — pointing the block at an empty template — **did not work and made
things worse**: `_toHtml()` filters `$page->getContent()` directly and ignores the
template entirely (`module-cms/Block/Page.php:164`), so the Arabic homepage started
rendering its legacy Fbuilder sections below the fold. Reverted; the title is now
set explicitly in the theme's `<head>`.

**Trade-off, stated plainly:** the homepage title now lives in theme layout rather
than admin. It is the same string for both store views today so nothing is lost
now, but if it needs to be merchant-editable or differ per store, point
`web/default/cms_home_page` at a new empty CMS page instead. That was not done
during go-live because it adds a second step to the rollback.

### 4. Five `CRITICAL` log lines on every single page view

Measured immediately after the switch: **5 per render** on the homepage, every
category page and the Arabic homepage alike.

```
CRITICAL: Invalid template file: 'products/grid.phtml'
          in module: 'MGS_Fbuilder' block's name: 'products\category_0'
```

`products/grid.phtml` and `widget/owl_banner.phtml` ship **only inside
`app/design/frontend/Mgs/supro`**, never in the module, so on a Magento/blank-based
theme they cannot resolve. The blocks come from Fbuilder widget directives embedded
in the Luma demo CMS blocks that the "Home Page" and "<x> Category Content" widget
instances render.

At any real traffic level that is unbounded growth in `system.log`, and a runaway
log has already filled this server's disk to 96% once.

Fixed **without deleting the demo content**: the theme now ships both templates as
no-op stubs, so the blocks resolve and output nothing. Also removed
`builder_panel`, which was emitting a hidden admin toggle (`#active-fbuilder`, 0×0)
into anonymous storefront HTML. Now **0 lines across all 13 pages**.

### 5. I broke the homepage mid-fix, and the guard caught it

While writing the comment for fix 3 I typed a literal double-hyphen inside an XML
comment. That is illegal, Magento **silently ignores the file with nothing in the
log**, and the homepage dropped from 147 KB to 91 KB — every custom section gone.

`docs/design/validate-theme-xml.sh`, written after this exact trap bit the exact
same file in an earlier phase, caught it immediately. That is the second time; the
guard has now paid for itself.

## Known and unchanged

- ~~`design/theme/ua_regexp` still contains the `HubMarketPreview` rule.~~
  **Removed 2026-08-06 13:44** — `core_config_data` row `config_id=2416` deleted
  outright rather than blanked, so no empty value is left for Magento to
  unserialise on every design-config read. `design/theme/*` is now a single row:
  `theme_id = 11`. Verified afterwards that a plain User-Agent and one containing
  `HubMarketPreview` return byte-identical pages (137 KB `/en/`, 147 KB `/ar/`),
  which is the actual proof the rule is gone rather than merely inert.

  Its value, if it is ever needed again:
  `[{"regexp":"\/HubMarketPreview\/","value":"11"}]`
- Homepage `<h1>` count is **2** on the Arabic store — a duplicate-heading issue that
  predates this work.
- Several Luma demo products have missing image files; unrelated to the theme.
- Store config remains genuinely mixed: EGP currency and Egyptian payment methods
  against an `ar_SA` locale and `Asia/Riyadh` timezone. Worth a decision, untouched here.
- MGS modules all remain enabled. Phase L (decommission) is now *unblocked* by this
  switch — see [PHASE-L-ASSESSMENT.md](PHASE-L-ASSESSMENT.md) for the sequence, the
  data at risk, and why `MGS_GDPR` should stay.
