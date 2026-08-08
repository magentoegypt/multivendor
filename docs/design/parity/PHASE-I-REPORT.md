# Phase I — cross-verification sweep

**66 captures**: 11 pages × 3 breakpoints (1440 / 768 / 390) × 2 locales (en LTR, ar RTL).
Raw JSON per capture in [`runs/`](runs/); driver at `scratchpad/sweep.mjs`.

Pages covered: home, PLP, PDP, seller directory, vendor microsite, bundles, cart,
Amasty checkout, login, CMS (about-us), 404.

## How it ran

The sweep drives one headless Chrome over CDP, applying breakpoints with
`Emulation.setDeviceMetricsOverride` rather than relaunching, and evaluates
[`harness.js`](harness.js) in the page. Two things it has to get right:

- **It uses a session profile with items in the cart.** With an empty cart Magento
  redirects `/checkout/` to the cart, and you silently measure the wrong page.
- **It waits on the real clock**, not `--virtual-time-budget`. Virtual time
  fast-forwards timers and fires before Knockout renders, which produced a blank
  Amasty checkout capture in Phase G.

## What the sweep found, and what changed

### 1. The whole storefront was rendering in Cairo, not DM Sans

The single biggest finding. `font-family: Cairo, "Open Sans", "Helvetica Neue"` was
set on `.page-wrapper` — **472 elements per page, 28,487 across the sweep**.

Source: **41 stylesheets** were loading, and `MGS_Fbuilder` injects a *generated*
file from media (`/media/mgs/fbuilder/css/3/fbuilder_config.min.css`) through a
head **block**, not a `<css src>` — so `<remove src>` cannot reach it. It carried
the retired Supro theme's typography and overrode the token layer.

Fixed in [`Magento_Theme/layout/default_head_blocks.xml`](../../../app/design/frontend/MagentoEgypt/hub-market/Magento_Theme/layout/default_head_blocks.xml):
removed `fbuilder.head.init`, `themesetting_config`, and the MGS ThemeSettings /
Mmegamenu / Brand stylesheets.

| | before | after |
|---|---|---|
| Stylesheets loaded | 41 | **27** |
| Cairo elements | 28,487 | **0** |
| DM Sans elements (home) | — | **2,383** |

### 2. Contrast: 178 → 118 failures

Two causes, one real and one a flaw in my own tool.

- **Real**: the hero CTA `a.hm-btn--primary` rendered **white on `#f26522` (3.15:1)**
  instead of navy. Now `rgb(15,33,68)` on `rgb(242,101,34)` = **5.04:1**.
- **Real**: Vnecoms ships vendor meta at `#888888` — 3.3:1 on the muted surface.
  Now `--hm-foreground-muted` (4.83:1). That was 40 of the failures.
- **Tool flaw**: the harness counted **visually-hidden** text. Skip links and
  `sr-only` labels are *clipped*, not hidden — they keep a box and a non-`hidden`
  visibility — so a 1.07:1 "failure" was reported for the search field's sr-only
  label on 10 pages. `harness.js` now skips `clip-path: inset(50%)`.

### 3. Layout overflow on the seller directory

`scrollWidth` **1468 against a 1440 viewport**, at 1440, 768 *and* 390, in both
locales. Cause: `.seller-info` was **content-box**, so `inline-size: 100%` plus
32px of padding overflowed its grid track.

This is the third time this exact trap has bitten — Magento/blank sets **no global
`border-box`**. Worth treating as a standing rule: any component with both a
percentage width and padding needs `box-sizing` set explicitly.

All three affected pages now pass.

### 4. Missing `<h1>`

`bundles`, `microsite` and `notfound` rendered **no `<h1>`**. The bundles hero is
now an `h1`. The other two are noted below.

## Current state

| Gate | Result |
|---|---|
| Overflow (66 captures) | **0 failures** on re-verified pages |
| Fonts | DM Sans / Playfair / IBM Plex Sans Arabic only (Arial ×90, Open Sans ×6 residual) |
| Direction | 33/33 `ar` captures `dir=rtl`, 33/33 `en` captures `dir=ltr` |
| Skip link | present on every capture |
| `<bdi>` isolation | present on every capture |
| `img` without `alt` | 0–2 |

## Still open

- **`aria-live` is 0 everywhere.** No live regions, so cart and filter updates are
  silent to screen readers. Needs template work, not CSS.
- **No `<h1>` on the vendor microsite or the 404.** Both are third-party/CMS
  markup; fixing them means a template override.
- **`#575757` and `#ffffff`-on-accent still appear in the colour census.** Probing
  the live DOM found **zero visible instances** — they come from the closed
  category-nav panel, which the harness counts but a user never sees. The census
  over-reports inside `<details>`; worth a follow-up refinement to the harness.
- **Contrast is 118, not 0.** The remainder is concentrated in PDP (48) and
  microsite (40) — largely third-party Vnecoms markup.
