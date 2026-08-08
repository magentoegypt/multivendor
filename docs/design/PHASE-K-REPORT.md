# Phase K — seller panel

Phase K turned out to be two jobs, because the first thing the mapping found was
that the seller panel had not been rendering CSS at all since **2026-06-24**.

## 1. The outage

`pub/static/vendors/` contained **one file** (`requirejs-config.js`, mtime
2026-06-24 23:40). All twelve stylesheets the panel links returned **404**. Every
vendor was looking at raw HTML in Times New Roman.

| | before | after |
|---|---|---|
| Applied CSS rules | **0** | 7,560 |
| Body font | `"Times New Roman"` | DM Sans / IBM Plex Sans Arabic |
| Stylesheets resolving | 0 / 12 | 14 / 14 |

Not caused by this project — it predates the theme work by six weeks, and the
Phase J deploy only ever touched `pub/static/frontend`. The vendors area was
simply never redeployed after the 2.4.8 upgrade.

### The locale that nearly got missed

The first fix deployed `en_US` and `ar_SA`. That was wrong:

```
ves_vendor_config → general/locale/code
   ar_EG   9 vendors
   en_US   1 vendor
```

**Nine of ten vendors run the panel in `ar_EG`**, which had no static content at
all, so for them the outage would have survived the fix. All three locales are now
deployed.

## 2. The design layer

New module **`MagentoEgypt_SellerTheme`** — CSS only.

### Why a module and not a theme

The vendors-area theme is hardcoded in `vendor/vnecoms/module-vendors/etc/di.xml`:

```xml
<type name="Magento\Theme\Model\View\Design">
  <argument name="themes"><item name="vendors">Vnecoms/vendor</item></argument>
```

It cannot be switched from the admin, and switching it in `di.xml` would force a
`setup:di:compile`. Instead the module contributes LESS through the theme's own
collector directive, which needs neither.

**Injection slot:** `view/vendors/web/css/source/_extend.less`. `styles-m.less`
ends with three collectors in the order `_module.less`, `_widgets.less`,
`_extend.less` — so `_extend.less` is emitted **last, after every module's
`_module.less`**, whatever order modules load in. That matters here: both
`Vnecoms_VendorsPageBuilder` and `MagentoEgypt_VendorExtend` already occupy the
`_module.less` slot. Taking the last slot wins on source order with **no
`<sequence>`, no `!important`, and no `di:compile`**.

Deliberately absent from the module: **no `setup_version`** (declaring it without
running `setup:upgrade` makes `DbStatusValidator` 500 the whole storefront — that
has happened on this install before), **no `di.xml`**, no plugins, no preferences.

### Files

```
app/code/MagentoEgypt/SellerTheme/
  registration.php · etc/module.xml
  view/vendors/layout/default.xml          <- stamps the `hm-seller` body class
  view/vendors/web/fonts/                  <- 10 woff2 (DM Sans, IBM Plex Sans Arabic)
  view/vendors/web/css/source/
    _extend.less            entry point
    _hm-seller-tokens.less  · _hm-seller-fonts.less · _hm-seller-base.less
    _hm-seller-shell.less   · _hm-seller-controls.less · _hm-seller-data.less
    _hm-seller-login.less   · _hm-seller-rtl.less
```

## Three traps that were measured rather than assumed

### The specificity bug that no screenshot would have caught

AdminLTE's skin sheet prefixes ~80 colour rules with `.skin-blue`, which the panel
carries as a body class:

```
.skin-blue .main-sidebar                             (0,2,0)
.skin-blue .main-header .navbar .nav .open>a:hover   (0,6,1)
```

A bare `.main-sidebar { background: navy }` is `(0,1,0)` and **loses on
specificity no matter how late it sits in the cascade**. The login screen renders
no header and no sidebar, so this was invisible in every capture — it would have
surfaced only once a seller logged in.

Fixed by stamping a `hm-seller` body class from layout XML and wrapping the shell
in it. That adds one class *and* one element, so every mirrored rule lands one
element ahead of its skin counterpart and wins the tie:

```
body.hm-seller .main-header .navbar .nav .open>a:hover   (0,6,2)  wins
```

A body class rather than `!important`, because the skin system is legitimate — a
seller can pick a skin at `/vendors/theme/index/customize` — and `!important`
would make future overrides impossible rather than merely harder.

### Static deploy silently skips a LESS target that already exists

Two consecutive deploys reported `exit=0` and copied the new fonts, yet
`styles-m.css` kept its **11:27 mtime** and contained none of the new CSS. The
publisher compares the root `.less` mtime (2026-06-15) against the output and
skips — it never sees a newer *transitive* import. `--force` does not change this.

The output directory has to be removed before the deploy. Same lesson as Phase J,
different area.

### RTL: physical properties cannot be overridden with logical ones

AdminLTE's geometry is entirely physical — `left: 0`, `margin-left: 230px`,
`float: left`. Declaring `margin-inline-start: 230px` after it does **not** replace
it: under RTL the logical property resolves to `margin-right`, so the element ends
up with 230px on *both* sides. `_hm-seller-rtl.less` is therefore deliberately
physical, and scoped to RTL; every *new* rule elsewhere in the module is logical.

Magento emits `<html lang="ar">` with **no `dir` attribute** here too, so
`html:lang(ar) { direction: rtl }` is what actually engages RTL.

### A dormant RTL mechanism worth knowing about

`Vnecoms_VendorsLanguage` ships a 252-line `layout-rtl.css` plus an observer that
adds a `layout-rtl` body class — but it only fires when `vendors/design/rtl_language`
contains the current locale, **and that config row does not exist**. So Vnecoms'
own RTL support has been switched off the whole time, which is why Arabic vendors
got a left-to-right panel.

If it is ever switched on, two of its rules out-specify the base styling
(`.layout-rtl .sidebar-menu > li > a` sets `border-right: 3px solid transparent`,
killing the accent active marker). `_hm-seller-rtl.less` re-asserts both, so the
panel looks the same whichever mechanism drives RTL.

## Colour

All **38** pairings computed with a real WCAG relative-luminance calculation, not
estimated. **0 failures.** Highlights:

| | ratio | |
|---|---|---|
| sidebar text on navy | 10.56:1 | |
| sidebar active label | 12.95:1 | |
| primary button — navy on accent | 5.04:1 | white on accent would be 3.15:1 and fail |
| table header | 8.72:1 | |
| status label (default) | 6.18:1 | was exactly 4.50:1; a pill with no margin is fragile |

Focus rings come in a pair on purpose: `@hm-accent-strong` is 5.18:1 on white but
only **3.07:1 on navy**, so anything on a navy surface uses the inverse ring.

## Also fixed: the seller registration wizard (frontend)

The real seller login is **not** a vendors-area page — every protected panel URL
302-redirects to `/marketplace/seller/login/`, a *frontend* route. It and the
registration wizard are styled in the storefront theme, not this module.

The login screen needed nothing: it reuses the core customer-login markup the
storefront theme already covers. The registration wizard did — its step indicator
came from `Vnecoms_VendorsCustomRegister` in a coral (`#fb6b5b`) belonging to no
palette here, with white text at **2.85:1 — a WCAG 1.4.3 failure** on the one
control telling a seller where they are in a five-step form. Now accent fill with a
navy label at **5.04:1**, and the step rail scrolls instead of crushing five labels
at 390px.

Added as `_hm-seller-onboarding.less`.

## What I could not verify, and it is a real gap

**Every authenticated screen is unverified.** I have no vendor credentials, and
creating an account is not something I will do unprompted. Verified directly: both
login screens, registration, and the seller directory, in both locales.

Styled from the class map but **never seen rendered**: dashboard, product list and
product form (full Magento UI components), order list and order view, reports,
commission, withdrawal/credit, shipping, coupons, RMA, notifications, config.

The specificity bug above is exactly the kind of defect that hides there. If you
give me a demo vendor login I can complete the pass; otherwise treat the
authenticated screens as built-but-unproven.

## A side effect I caused and repaired

`bin/magento module:enable` cleared `generated/` — including `generated/metadata`,
the compiled DI config. The site stayed up but rebuilt DI per process; three
requests timed out at 25s before recovering. I ran `setup:di:compile` (2m53s,
exit 0) and `generated/metadata` is restored to 18 entries. All pages 200 after.

Worth knowing for next time: on this install, enabling *any* module in production
mode implies a recompile.

## Deployment state

```
pub/static/vendors/Vnecoms/vendor/{en_US, ar_SA, ar_EG}   — deployed, 12:07
pub/static/frontend/MagentoEgypt/hub-market/{en_US, ar_SA} — redeployed, 12:09
```

Unlike the storefront theme, **this is live for every vendor right now** — a
module-injected stylesheet has no equivalent of the `ua_regexp` preview gate. It
is reversible with `bin/magento module:disable MagentoEgypt_SellerTheme` followed
by a redeploy of the vendors area.

## Out of scope, deliberately

- **No screen redesigns.** The Figma covers the storefront only; nothing in the
  seller panel was ever drawn. This is a colour/type/spacing skin over existing
  layouts.
- **No `.phtml` or markup changes.** Which means the accessibility defects that
  need markup are still open: **no `<h1>` on either login screen**, no skip link,
  no `aria-live`, and the registration page emits **two `<h1>`s**.
- **No touching `vendors/design/skin`** or the seller-facing theme customiser.
- **No ExtJS restyling** — `web/extjs/` sits outside both Bootstrap and the tokens.
- **AdminLTE.css line 1 imports `fonts.googleapis.com`** — a third-party font
  request that MGS_GDPR arguably should be consenting for. Removing it needs a
  layout `<remove>` plus a forked copy of a 4,729-line vendor file. Flagged, not done.
- The seller registration still offers **"Porto Demo 1–4"** vendor themes, one with
  a broken preview image — leftovers from the retired Porto theme, and content
  rather than styling.
