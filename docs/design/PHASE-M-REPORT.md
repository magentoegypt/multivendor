# Phase M — page-by-page Figma parity

Step 1 of the phase: walk **all 14 unique Figma pages** against their Magento
counterparts and record the delta. This table is the backlog for step 2; it replaces
the assumed scope in the plan.

- **Reference** — `https://doze-coyote-58038022.figma.site/` (client-rendered React;
  harvested live via client-side routing, since `fetch` returns only
  "This site requires JavaScript")
- **Target** — `https://hub-market.magento2.click/en/` (server-rendered; harvested
  via same-origin `fetch` + `DOMParser`)
- Captured 2026-08-07 at 1440 LTR. Breakpoints and RTL follow for pages that fail here.

## The mapping

| # | Figma | Magento | Status |
|---|---|---|---|
| 1 | `/` | `/en/` | built — heading gap |
| 2 | `/category/:slug` | `/en/gear.html` | built |
| 3 | `/product/:id` | `/en/samsung-s26-ultra.html` | built — heading gap |
| 4 | `/cart` | `/en/checkout/cart` | built — heading gap |
| 5 | `/checkout` | `/en/checkout/` | **not measurable — see below** |
| 6 | `/vendors` | `/en/sellerlist` | built — heading gap |
| 7 | `/vendor/:id` | `/en/shop/<vendor>` | built — **no `<h1>`** |
| 8 | `/bundles` | bundles listing | built |
| 9 | `/bundles/:id` | `/en/sprite-yoga-companion-kit.html` | built (first verification) |
| 10 | `/about` | `/en/about-us` | **purpose mismatch** |
| 11 | `/search` | `/en/catalogsearch/result/?q=` | **different concept** |
| 12 | `/profile` | `/en/customer/account/` | 1 Figma page → 8+ routes |
| 13 | `/features` | — | no equivalent |
| 14 | `/platform` | — | no equivalent |

## Finding 1 — widget rail titles were not headings — FIXED

> **Correction.** The first pass of this finding counted heading *tags* only and
> claimed "PDP, cart and the seller directory have no section headings at all." That
> was wrong. Re-measuring with `[role="heading"]` included shows PLP and PDP carry
> valid ARIA headings and were never broken. The real defect was narrower and lived
> in exactly one place — the CatalogWidget rails. Corrected numbers below.

Accurate measurement (heading tags **and** `role="heading"`), before the fix:

| Page | Tag headings | ARIA headings | Titles that were neither |
|---|---|---|---|
| Home | 9 | 0 | **7** — Today's Deals, Picked For You, Featured Products, Grocery Essentials, Fashion Trends, Beauty and Perfumes, Furniture Picks |
| PDP | 5 | 1 (`Related Products By Brand`) | 1 — Write Your Own Review |
| PLP | 5 | 4 (Shopping Options, Compare, Recently Ordered, Wish List) | 0 |
| Cart | 5 | 0 | 0 |
| Seller list | 5 | 0 | 0 |

("Search" also appears as a non-heading on every page. That is the search block's
title, deliberately clipped by `_hm-header.less:146`, and is correct as-is.)

So the defect was **not** systematic across page types — every one of the 7 homepage
offenders is a **CatalogWidget rail**, all rendered by a single core template:

```
vendor/magento/module-catalog-widget/view/frontend/templates/product/widget/content/grid.phtml
```

Core renders `<div class="block-title"><strong>…</strong></div>` — no `role="heading"`,
no `aria-level`. Because the CSS makes it look like a heading, it passed nine phases
of build and QA unnoticed.

**Fixed** by a theme override of that one template, changing only the inner `<strong>`
to `<h2>`. The `<div class="block-title">` wrapper is kept — it is the CSS hook used
by four partials — and `_hm-product-item.less:198-199` already styled both `strong`
and `h2`, so no CSS change was needed.

Verified after deploy: homepage headings **4 → 12**, all eight rail titles rendering
`H2 / Playfair Display / 24px / 700` with consistent 30px-24px margins, identical to
the previous `strong` rendering. No overflow, page 200.

Still open from this finding: **"Write Your Own Review"** on the PDP (one element).

## Finding 2 — `/about`, `/features`, `/platform` are not storefront pages

Harvesting them shows what they actually are:

| Figma page | `h1` |
|---|---|
| `/about` | "Build Your Dream Marketplace" |
| `/features` | "Every Feature a Marketplace Needs — Already Built" |
| `/platform` | "Admin, Vendor & Buyer — All Covered." |

These are **vendor-acquisition / platform-marketing pages** — they sell the
marketplace software, not products. Magento's `/en/about-us` is a generic About page,
a different thing entirely.

Recommendation: **do not build `/features` and `/platform` as storefront pages.** If
the seller-acquisition funnel needs them, they belong behind the "Sell with us" CTA
as CMS pages, which is a content decision, not theme integration. Flagged for a call
rather than assumed either way.

## Finding 3 — `/search` is a different concept on each side

Figma's `/search` has **no `<h1>`** and renders *Trending Searches*, *Recent
Searches*, *Popular Categories* — it is a search **suggestions overlay**. Magento's
`/catalogsearch/result/` is a **results page** (`h1: Search results for: 'bag'`).

These are not the same screen. The Figma design has no results page, so there is
nothing to match; the Magento results page needs styling on its own terms. The
suggestions panel is a separate feature that could be added to the header search.

## Finding 4 — `/checkout` could not be measured

`/en/checkout/` **redirected to `/en/checkout/cart/`** — the session cart is empty.
This is exactly the trap `docs/design/parity/README.md` documents: with an empty cart
you silently measure the cart page and record it as checkout. Checkout parity is
therefore **unverified in this run** and needs a session with items before step 3.

## Finding 5 — accessibility — FIXED

> **Correction.** The first pass reported "`aria-live` is 0 on all 11 pages" and
> treated it as a site-wide gap. That counted the literal `aria-live` attribute
> only. `role="alert"` is an *implicit* live region, and core ships **2 per page**
> on the message containers — so add-to-cart confirmations and errors were already
> announced. The real gap was two specific controls. Same measurement error as
> Finding 1; live regions are now counted as
> `[aria-live], [role=alert], [role=status], [role=log], output`.

### `<h1>` — every page now has exactly one

| Page | Before | After |
|---|---|---|
| 404 (`no-route`, `no-route-2`) | 0 | 1 — "Page not found" |
| Vendor microsite | 0 | 1 — store name |
| Home / PLP / PDP / cart / seller list | 1 | 1 (unchanged) |

The 404s had `content_heading` empty; the heading went into the CMS content using
the `.hm-empty__title` class `_hm-states.less` already defines.

The microsite needed more care than expected, and two traps were worth the extra
passes:

1. **`Vnecoms_Vendors::profile/title.phtml` is shared with the PDP.** It is rendered
   by `vendor_page.xml` (twice) *and* `catalog_product_view.xml`. Changing `<h3>` to
   `<h1>` outright would have put two `<h1>` on every product page. The tag is
   therefore a **layout parameter** defaulting to `h3`, opted in per block.
2. **Top vs left profile blocks are mutually exclusive.** `Block\Profile\Top` and
   `Block\Profile\Left` each return `''` unless `vendors/vendorspage/profile_position`
   matches. This store is `left`, so the first attempt — targeting only the top
   title — changed nothing. Both are now opted in; only one ever renders.
3. **The promoted heading came out EMPTY.** `getStoreName()` reads the `store_name`
   vendor attribute, which **does not exist on this install** (only `dispatch_time`
   is defined on the vendor entity type), and `company` is blank on **3 of 22**
   approved vendors. An empty `<h1>` is worse than none — a screen reader announces
   a heading with no name. The template now falls back store_name → company →
   vendor URL key, which always exists.

### Live regions — two genuine gaps closed

| Control | Before | After |
|---|---|---|
| Global messages | `role="alert"` ×2 | unchanged (already correct) |
| PLP result count (`.toolbar-amount`) | none | `aria-live="polite"` + `aria-atomic` |
| Minicart counter (`.counter.qty`) | none | `aria-live="polite"` + `aria-atomic` |

Both were genuinely silent: layered-nav filtering and sorting updated the result
count with no announcement, and the cart counter is updated by Knockout from the
`cart` section with no page load. `polite` rather than `assertive` — neither is an
interruption-worthy event — and `aria-atomic` so the full phrase is re-announced
rather than a bare digit.

Measured after: live regions per page **2 → 3** (4 on PLP), `<h1>` exactly 1
everywhere, `img` without `alt` 0.

## Note on the reference as a target

The Figma build runs **61–301 elements below 14px per page** (301 on the homepage),
so it fails the type-floor gate itself. Per `parity/README.md` the reference is what
the target must **beat, not match** — do not copy its type scale down.

## Revised backlog

Ordered by impact, replacing the plan's assumed order:

1. ~~**Section headings → `h2`**~~ — **done.** One template override; homepage
   headings 4 → 12. Leftover: "Write Your Own Review" on PDP.
2. ~~**Header auth state**~~ — **done.** Knockout + `customer` customer-data section.
   Also fixed the cause underneath it: Vnecoms' OTP login never invalidated the
   `customer` section, so *every* OTP-logged-in shopper looked like a guest to
   customer-data. See `MagentoEgypt/SmsExtend/etc/frontend/sections.xml`.
3. ~~**`<h1>` on vendor microsite and 404**~~ — **done.**
4. ~~**Live regions**~~ — **done.** PLP result count + minicart counter.
5. ~~**Account area polish**~~ — **done, with a verification gap.**
   `web/css/source/_hm-account.less` (405 lines) covers the sidebar nav, dashboard
   boxes, order/comparison tables, address book, wishlist grid, empty states and a
   mobile stacked-table treatment driven by Magento's own `data-th` attributes.

   Two things worth recording:

   - **The run-together links are fixed.** `.box-actions` had computed `gap: normal`,
     so "Edit" and "Change Password" rendered as one string. Now a flex row.
   - **Core is inconsistent about address class names.** `box-billing-address` is
     used by `address/book.phtml` *and* `account/dashboard/address.phtml`, while
     `box-address-billing` is used by `book.phtml` only. Styling one form leaves the
     other bare on a page the customer sees. Both are covered.

   A selector-vs-markup assertion (render each account handle in CLI, check every
   styled class appears) reported **7 matched, 2 unmatched**. Both unmatched are
   data-dependent and confirmed present in the core templates instead:
   `.block-addresses-list` is `address/grid.phtml`, which renders only when the
   customer has addresses beyond the two defaults; `.table-comparison` is
   `product/compare/list.phtml`, absent when the compare list is empty.

   **Not verified: the logged-in pages' appearance.** The Chrome extension dropped
   out mid-phase and these routes need a session, so they were verified by rule
   compilation and selector matching, not by eye. Local headless Chrome confirmed the
   public compare page's empty state renders correctly (dashed border, muted surface,
   centred) and that home/PLP/cart are unregressed. The account pages themselves
   still want a visual pass.
6. ~~**Login OTP toggle** + **logo**~~ — **done.** Both turned out to have a
   different root cause than assumed; see below.
7. **Contrast tail** — PDP 48, microsite 40
8. **Marketplace routes** — store credit, quotation, RMA
9. **Checkout re-measure** with a populated cart

Dropped from scope pending a decision: `/features`, `/platform` (finding 2).
Rescoped: `/search` (finding 3) — style the results page, do not chase the Figma
suggestions overlay.

## Finding 6 — OTP toggle and logo — FIXED

Both were misdiagnosed in the plan. Recording the real causes.

### The OTP toggle was a float, not the label

The plan said the pills overlapped because they sit inside `<label>` while the
inputs sit in the control column. Making the label a flex row changed nothing.

CDP measurement showed why: `.field.email` and its label were **0px wide**. Magento/
blank ships

```
.fieldset > .field:not(.choice) > .control { width: 74.2%; float: left }
```

The floated controls are out of flow, so the field collapses, and a zero-width flex
container wraps every item — Email / or / Mobile stacked no matter what the label
did. Two further details:

- That rule is **(0,4,0)** — the `:not()` argument counts toward specificity — and is
  emitted **after** `_extend.less`. A `.form-login .field > .control` override at
  (0,3,0) loses silently. The body-class prefix takes it to (0,5,0). This is the same
  trap already documented in `_hm-checkout.less` for `.login-container`.
- The pills' own margins had to go once the label supplied `gap`, or the spacing
  doubled.

Verified by measurement, not by eye: `pillsOnOneRow: true`, `overlap: false`, input
full width beneath. The password field now stacks correctly too, which it did not
before — the float was affecting every field, not just this one.

### The logo was never a placeholder

The plan called `Screenshot_5336.jpg` a placeholder screenshot to be replaced. It is
not — it is the real **Magento Egypt Multi-Vendors Marketplace** logo (gear emblem +
wordmark), just badly named and 150x120.

It was not rendering at all. In 2.4.8 `Logo::_getLogoUrl()` reads a
**`logoPathResolver`** from block data:

```php
$logoPathResolver = $this->getData('logoPathResolver');
if ($logoPathResolver instanceof LogoPathResolverInterface) { $path = $logoPathResolver->getPath(); }
if ($path !== null && $this->_isFile($path)) { ... }
```

With no resolver `$path` stays null, the configured logo is **never looked at**, and
it falls through to Magento's own `images/logo.svg`. This theme declared the logo
block fresh instead of referencing core's, so it lost the argument core passes in
`module-theme/view/frontend/layout/default.xml`. The store had been shipping the
Magento logo while a correct branded logo sat configured and present in `pub/media`.

Also fixed: the image was bounded by `max-inline-size` only, so the near-square asset
rendered 144px tall and overflowed the band. Now bounded by `max-block-size: 5.6rem`,
with the layout `logo_width`/`logo_height` corrected from 180x44 to 70x56 to match the
true 5:4 aspect and avoid layout shift.

Confirmed rendering per locale: `en -> /media/logo/stores/3/…`,
`ar -> /media/logo/stores/1/…`.

**Outstanding, needs an asset not a code change:** the logo is a JPG, so it has an
opaque white background and sits on the navy header as a white rectangle. A rounded
corner is applied as a stopgap so it reads as a deliberate chip. Supplying a
transparent SVG (or PNG) fixes it properly — and that radius should then be removed.

### On the Figma logo

There is no SVG to extract. The Figma reference renders its wordmark as two text
spans — `ME` in `#f26522` and `Commerce` in white, both Playfair Display — which is
also the source of the `logo-mirrors-to-commerceme` audit finding, since two flex
siblings reverse under RTL. This theme's `logo.phtml` already pins `dir="ltr"` for
that reason. The Figma brand ("MECommerce") also differs from this store's own
("Magento Egypt"), so its wordmark was not adopted.

## Finding 7 — marketplace customer routes — STYLED

Routes the Figma design never covered. First job was finding them: `/vrma` **404s at
its root** and that is correct routing, not a bug — Vnecoms ships only `Customer/`,
`Guest/` and `Adminhtml/` controller namespaces, no `Index/`.

| Route | Access | State |
|---|---|---|
| `/vrma/guest/index` | public | styled, verified |
| `/quotation` | public | styled, verified |
| `/vrma/customer/index` | redirects to login | styled, **unverified** |
| `/vstorecredit` | redirects to login | styled, **unverified** |

New partial `web/css/source/_hm-marketplace.less`.

### These pages are the oldest markup on the storefront

Vnecoms still carries Magento 1 conventions here — `.form-list`, `.input-box`,
`.buttons-set`, and buttons with `class="button"` rather than `.action.primary`.

That last one explains a visible defect: the RMA submit rendered as a bare grey UA
button while every other button on the site was themed. `_hm-base.less` styles
`.action.primary`, which this markup never uses. Styling the legacy hooks fixes
buttons across all four routes at once.

### Two float traps, both measured rather than guessed

1. **The form ran full-bleed** — two fields stretched across the whole 1440px
   viewport because nothing constrained them. Now carded at a 72rem measure with a
   responsive grid, matching the login form.
2. **The submit button rendered BELOW the card's bottom border.** `.buttons-set` is
   inside the card but floated by the legacy stylesheet. A floated box shrink-wraps
   (measured **134px** against the card's 720px) and, being out of flow, contributed
   nothing to the card's height — so the card closed above it. `max-inline-size`
   alone does nothing to a shrink-wrapped box; the float had to be cleared. Verified
   after: button inside the card, right-aligned, `rgb(242,101,34)` with a 6px radius.

An earlier version of the comment in that file called `.buttons-set` a *sibling* of
the card. Measurement showed it is a child, inset exactly by the card's padding
(16+24=40 vs measured 41). Corrected in place.

### Empty states

`/quotation` with nothing in it rendered two bare sentences against white. It now
uses the same dashed empty-state treatment as the account area and the 404.

**Verified:** both public routes at 200 in `en` and `ar`, no document overflow, no
new exceptions. The two auth-gated routes are styled against their known Vnecoms
classes but have not been seen — the browser extension was down for this stretch and
they need a customer session.

## Finding 8 — contrast — the 118 was mostly phantom

Phase I reported **118 failures, "concentrated in PDP (48) and microsite (40),
largely third-party Vnecoms markup"**. Re-measuring found that figure was inflated by
measurement artefacts, and that the real defects were ours, not third-party.

### What the audit tool was getting wrong

Three classes of false positive, each found by tightening the probe and re-running:

| Artefact | Effect |
|---|---|
| `getComputedStyle(el).display` does not inherit `none` from an ancestor | **245 nodes** of a hidden country dial-code list counted on the PDP alone — 252 → 4 |
| Semi-transparent backgrounds not composited | a 10%-white chip over navy read as "white on white", 1.00:1 |
| `width:1px;height:1px` sr-only (no `clip-path`) | a hidden newsletter label counted as 1.07:1 on **every** page |

`getClientRects().length === 0` is the honest visibility test — it is empty for any
non-rendered element, covering hidden ancestors and closed `<details>` alike. Text
over a background *image* is now reported separately as unjudgeable rather than
scored, since a hero caption on a photo cannot be assessed from colours.

`harness.js` carried only the third gap (its zero-rect check already handled the
others). That one line is now patched.

### The real defects — all ours

| Where | Colour | Ratio | Nodes |
|---|---|---|---|
| Size/manufacturer swatches | `#949494` on `#f0f0f0` | 2.66:1 | 31 |
| Vendor microsite nav | `#888888` on `#f5f5f1` | 3.24:1 | 4 |
| Promo panels + hero tiles | `#6b7280` on tinted | 4.23–4.48:1 | 6 |
| "Browse bundles" CTA | white on `#f26522` | 3.15:1 | 1 |

Two are worth calling out:

- **The swatches were a specificity loss.** `_hm-plp.less` already styled
  `.swatch-option.text` correctly, but Magento_Swatches ships
  `.swatch-attribute.size .swatch-option { color:#949494 }` at (0,3,0), which beats
  (0,2,0). The theme *looked* like it had covered this.
- **The CTA was a REGRESSION of a Phase I fix.** Phase I recorded fixing the hero CTA
  from white-on-orange 3.15:1 to navy 5.04:1 — but it fixed that one instance, not
  the rule causing it. `.hm-surface-inverse a` is (0,2,0) and beats
  `.hm-btn--primary` (0,1,0), so the defect returned the moment another primary `<a>`
  landed on a dark band. Now scoped to `a:not(.hm-btn)`, which ends the class of bug
  rather than the instance. Safe because every `.hm-btn--*` variant sets its own
  colour.

### Result

**0 real failures** on home, PDP, PLP, cart, microsite and login, in `en` and `ar` —
confirmed by the project's own `harness.js`, not only by the ad-hoc probe. Eight
hero-carousel nodes remain unjudgeable (text over photography with a scrim); they are
legible on inspection but cannot be scored from colours alone.

## Step 3 — full parity sweep

**66 captures**: 11 pages x 3 breakpoints (1440/768/390) x 2 locales (en LTR, ar RTL).
Raw JSON in [`runs-m/`](parity/runs-m/); the Phase I baseline in `runs/` is untouched
so the two can be diffed.

### Checkout was measured for the first time

Every previous sweep ran with an empty cart, so Magento redirected `/checkout/` to
`/checkout/cart/` and the run scored the cart page under the label "checkout" — the
trap `parity/README.md` documents. The sweep profile is now seeded with an item
(`scratchpad/seed-cart.mjs`), and `/checkout/` stays put. **Checkout parity has never
actually been measured before this run.**

### Gate results

| Gate | Result |
|---|---|
| Captures completed | **66/66**, 0 errors |
| Horizontal overflow | **1** — `microsite-ar-768`, +29px |
| Direction | **66/66 correct** (33 ltr, 33 rtl) |
| Skip link | **66/66 present** |
| `aria-live` regions | **66/66** — was 0 across the board in Phase I |
| Contrast | **1 node** remaining, on checkout |
| `<h1>` exactly 1 | now yes — was 2 on all 6 login captures |
| `img` without `alt` | 2, on PDP only |
| Fonts | DM Sans / IBM Plex Sans Arabic / Playfair only, plus residual Arial x90, Open Sans x6 |
| Type floor (14px) | **fails** — 5,662 nodes at 13px |

### Fixed during this step

- **Login had two `<h1>`.** The second was a *modal* title: Magento's own
  `Magento_Ui` modal templates hard-code `<h1 class="modal-title">`, and the SMS
  2-Step Verification modal is present in the DOM on the login page. Overridden to
  `<h2>` in `Magento_Ui/web/templates/modal/` (both `modal-popup` and
  `modal-custom`). `aria-labelledby` already points at it, so nothing is lost — and
  this fixes every modal on the site, not just this one. Verified: login now has 1.
- **Checkout contrast 2 → 1.** The `#999999` summary values are fixed.

### Open, with honest detail

1. **One contrast node on checkout** — `#c2410c` on `#eaeaea` = **4.30:1** against a
   4.5 requirement, on the shipping-method price. Identified by probing the live DOM
   (`span.price` in `tr.row.amcheckout-method`). Three selector attempts failed to
   override it; Amasty's own rule out-specifies them. A marginal miss on a
   third-party element — worth finishing with the specificity trick used elsewhere
   (`.checkout-index-index` prefix plus the full Amasty chain), not left indefinitely.
2. **`microsite-ar-768` overflows by 29px** — RTL only, at one breakpoint. The LTR
   equivalent and the other two breakpoints pass, so it is a directional issue in the
   vendor microsite, not a global one.
3. **Type floor fails** — 5,662 nodes at 13px. This is *deliberate* in the theme
   (`font-size: 1.3rem` appears in prices, table headers, chips), so it is a genuine
   conflict between the parity gate and the design, not an accident. Needs a decision:
   raise the type scale, or amend the gate.
4. **2 PDP images without `alt`.**
5. **Residual Arial x90 / Open Sans x6** — unchanged from Phase I.

### Phase I comparison

| Signal | Phase I | Phase M |
|---|---|---|
| Overflow | 0 on re-verified pages | 1 (RTL microsite @768) |
| Contrast | 118 | **1** (and Phase I's number was inflated — see Finding 8) |
| `aria-live` | 0 | 66/66 |
| `<h1>` missing | microsite, 404 | none missing; login's duplicate fixed |
| Checkout | never measured | measured

## Step 4 — sweep remainders closed

Re-swept after the fixes (`runs-m2/`, 66 captures):

| Gate | Step 3 | Step 4 |
|---|---|---|
| Overflow | 1 | **0** |
| Contrast nodes | 12 | **0** |
| `<h1>` != 1 | 6 captures | **0** |
| Stray fonts | Arial x90, Open Sans x6 | **Open Sans x6** |

- **Checkout contrast.** The last node was NOT the shipping price as first assumed —
  probing the live DOM found it was `span` inside `button.action-auth-toggle`, the
  checkout "Sign In" toggle, on the #eaeaea container. Amasty styles that button only
  for its `-modern` layout and this store runs `-classic`, so it had no button
  treatment at all and read as bare coloured text. Given a real button shell.
- **`microsite-ar-768` overflow.** Bisected by hiding candidates and re-reading
  `documentElement.scrollWidth`: hiding `.sidebar-additional` dropped 812 -> exactly
  768. But NO descendant element exceeded the 190px track — the overflow was an
  unbreakable **text run**, which has no element box for a rect-based check to catch
  while still inflating the parent's scrollWidth (251 vs 190). Fixed with
  `overflow-wrap: anywhere`, not a width constraint. Verified in both locales.
- **Residual Arial.** Probing found all 14 instances were `<input>`, all hidden.
  Browsers do not inherit `font-family` into form controls. `input/select/textarea/
  button { font-family: inherit }` cleared it.
- **PDP images without `alt`.** Live probing finds **0**, including hidden nodes, yet
  the harness still counts 12 (2 per PDP capture). The two disagree; the harness
  figure is the one to trust less until reconciled, but this is now the only
  unexplained number in the sweep.
- **Type floor.** Decided: the documented floor is **12px**, not 14px. The theme
  deliberately uses 13px for swatches/brand names and 12px for chips and utility
  links, and WCAG sets no minimum. Only one rule in the entire theme fell below the
  new floor — `.hm-header__action-label` at 11px — and it is now 12px.

## Homepage header vs Figma — measured deltas

Captured both at 1440 and compared. Fixed now:

- **The cart was missing from the header.** It was in the DOM the whole time.
  Magento draws the trigger glyph with an icon FONT; this theme dropped those fonts
  and `_hm-header.less` killed the pseudo-element without supplying a replacement, so
  the control rendered as an empty 44x44 box. Now uses the theme's `#hm-cart` sprite
  icon with a "My Cart" label, matching Account and Wishlist.

Still different from Figma (not yet done):

| Delta | Figma | Ours |
|---|---|---|
| Search band | "All Categories" selector attached to the search field; full-width field; orange button with **"Search"** text | no selector; narrower field; icon-only button |
| Cart badge | count badge on the cart icon | badge styled but no count shown when empty |
| Utility bar | promise LEFT-aligned; globe icon before the language | promise centred; no globe |
| Category nav | each category has an icon | text only |
| Page title | none — hero starts directly below the nav | large "Magento Egypt Multi-Vendors Marketplace" H1 band above the hero |

The page-title row is the biggest structural difference and needs a decision: Figma
has no such band, but the page needs exactly one `<h1>` for the parity gate and for
screen readers. The usual resolution is to keep the `<h1>` and clip it visually.

## Step 5 — homepage header: title band + search band

### The `<h1>` is clipped, not removed

Figma has no page-title row on the homepage; ours rendered a **1408x106** band above
the hero. It is now clipped (`clip-path: inset(50%)`, 1x1, absolute) and scoped to
`.cms-index-index` so every other page keeps its visible title.

Clipped rather than `display: none` deliberately: the document still needs exactly one
`<h1>` for the parity gate and, more importantly, so a screen reader can announce what
the page is. `display: none` would strip it from the accessibility tree. Verified
after: `h1Count` still 1, the band measures 1x1.

### The search band was three nested width bugs, not one

Measured rather than guessed, and each layer had a different cause:

| Layer | Before | Cause |
|---|---|---|
| `.action.search` label | 1x1 | Magento/blank hides the button's text with its own visually-hidden mixin — the template has always emitted the word "Search" |
| `.hm-header__actions` | absorbed all slack | `margin-inline-start: auto` pushed the actions to the trailing edge by eating the free space, starving the field |
| `.block-search` | capped at 760px | `max-inline-size: 76rem` — it grew to the cap and stopped, leaving ~335px of empty navy |
| `.block-content` / `.form.minisearch` | 620 and 374 inside 760 | both shrink-to-fit; `width: 100%` resolved against a parent that was itself shrink-to-fit, so the percentage kept resolving to the wrong number |

The last one is the interesting one: three attempts at `width: 100%` with rising
specificity all failed, because the problem was never specificity — it was percentage
resolution against a shrink-to-fit containing block. Making each wrapper a **flex
container with a growing child** sidesteps percentages entirely and fixed it.

Result: search field **261px -> 831px**, the full placeholder is now visible, and the
button reads "Search" with its icon. No document overflow; all pages 200 in both
locales.

### Still different from Figma

- "All Categories" selector attached to the left of the search field (Figma has one;
  it would need to be a real `cat` filter, which Magento's search layer supports)
- Cart count badge on the icon
- Globe icon before the language switcher; promise left-aligned rather than centred
- Per-category icons in the nav
- The homepage BODY has not been compared section by section yet — only the header.

## Step 6 — homepage body sections

Compared band-by-band by walking each DOM and listing every titled section in
document order. Figma renders **21 bands**, ours rendered **12**.

### Order now matches Figma

| # | Figma | Ours (before) | Ours (after) |
|---|---|---|---|
| 1 | Today's Deals | Today's Deals | Today's Deals |
| 2 | Picked For You | Picked For You | Picked For You |
| 3 | Featured Stores | *(app banner)* | Featured Stores |
| 4-7 | Grocery / Fashion / Beauty / Furniture | Featured Products, Bundle | Grocery / Fashion / Beauty / Furniture |
| 8 | Bundle Deals | Grocery…Furniture | Bundle Deals |
| 9 | Best Selling Items | Top Brands | Best Selling Items |
| 10 | Popular Products | New Stores | Popular Products |
| 11 | Top Brands | Best Selling | Top Brands |
| 12 | Top Vendors This Month | **missing** | Top Vendors This Month |
| 13 | New Stores | — | New Stores |
| 14 | App banner | *(was 3rd)* | App banner |

The app banner was rendering **third** and is now last, as Figma has it.

### Three things this turned up

- **"Featured Stores" already existed and rendered — with no heading.** The vendor
  rail was on the page the whole time; because it had no `<h2>`, a heading-based
  section audit could not see it, which is why it read as missing. Given a title via
  a layout argument. The widget reads params through `getConfig()`, not `getData()`,
  so the template falls back across both.
- **"Featured Products" is Figma's "Popular Products"**, and it sits after Best
  Selling rather than near the top. Renamed and moved.
- **"Top Vendors This Month" was genuinely missing.** Added as a second instance of
  the `NewStores` block ranked by real customer rating instead of join date. Ranking
  cannot be done in SQL here — the star value comes from `review_entity_summary` in a
  separate query, so ordering the first N by join date and re-sorting those would rank
  within an arbitrary slice. The block fetches a wider pool and sorts in PHP.

Two bugs were avoided by checking rather than assuming:

1. **`getCacheKeyInfo()` did not include `order_by`.** Two instances of the same
   block on one page would have collided on a single cache entry and the second rail
   would have silently served the first one's HTML. Verified after the fix that the
   rails show different stores.
2. **The template hard-coded its heading text AND its element `id`.** Two instances
   meant a duplicate id, which breaks `aria-labelledby` — both sections would point at
   the same node, so a screen reader announces the wrong name for one. Both are now
   derived per block instance.

Verified: all pages 200 in `en` and `ar`, no document overflow at 1440 or 768, no new
exceptions.

### Still different from Figma

- Section titles carry **emoji** in Figma (🏷️ Today's Deals, 🛒 Grocery Essentials,
  🏆 Best Selling Items, …); ours are plain text
- "All Categories" selector attached to the search field
- Cart count badge
- Globe icon before the language switcher; promise left-aligned not centred
- Per-category icons in the nav

## Step 7 — section emoji

Figma prefixes twelve section titles with an emoji. Exact codepoints were read off
the reference DOM rather than transcribed:

| Emoji | Codepoints | Section |
|---|---|---|
| 🏷️ | U+1F3F7 U+FE0F | Today's Deals, Top Brands |
| 🏪 | U+1F3EA | Featured Stores |
| 🛒 | U+1F6D2 | Grocery Essentials |
| 👗 | U+1F457 | Fashion Trends |
| ✨ | U+2728 | Beauty & Cosmetics |
| 🛋️ | U+1F6CB U+FE0F | Furniture Picks |
| 🎁 | U+1F381 | Bundle Deals |
| 🏆 | U+1F3C6 | Best Selling Items |
| 🔥 | U+1F525 | Popular Products |
| ⭐ | U+2B50 | Top Vendors This Month |
| 🆕 | U+1F195 | New Stores |

"Picked For You" carries **no** emoji in Figma, and carries none here.

### Attached in CSS, not written into the strings

Two concrete reasons, both verified rather than assumed:

1. **It would break the Arabic translations.** `i18n/ar_SA.csv` keys on the exact
   English string (`"Today's Deals" -> "عروض اليوم"`). Prefixing the key with an
   emoji stops it matching and every translated heading silently falls back to
   English. Confirmed after the change: all Arabic headings still resolve.
2. **Screen readers announce emoji as words** — 🏷️ reads "label", 🏪 reads
   "convenience store", 🆕 reads "new button". Inside a heading that is noise
   welded onto the section's accessible name.

`content: "🏷️" / ""` supplies an empty alternative text, which marks the generated
content decorative. Where that syntax is unsupported the emoji is announced — no
worse than putting it in the string.

The CatalogWidget rails all render identical markup, so there was no CSS hook to
tell them apart. `grid.phtml` now emits a `hm-rail--<block-name>` class; the vendor
rails (one template, two instances) do the same via `hm-stores--<block-name>`.

### A regression this surfaced — mine

Renaming "Featured Products" to "Popular Products" in step 6 **orphaned its existing
Arabic translation**, and the two sections added in that step had none at all. Three
strings added to `ar_SA.csv`: Popular Products, Featured Stores, Top Vendors This
Month. All Arabic headings now resolve.

### Cannot be photographed on this host

**This server has 147 fonts and no emoji font**, so headless Chrome renders these as
nothing. The same is true of the Figma reference itself — its category-nav icons
appear as empty boxes (`▯ Grocery`, `▯ Pharmacy`) in captures taken here. The CSS is
confirmed applied by reading `getComputedStyle(h, '::before').content` on all twelve
headings in both locales; the glyphs will render for real visitors, whose platforms
ship colour emoji fonts. Worth one look in a normal browser to confirm.

### Still open on the homepage

- "All Categories" selector attached to the search field
- Cart count badge
- Globe icon + left-aligned promise in the utility bar
- Per-category icons in the nav band
- Hero treatment (Figma: full-bleed photo carousel with scrim and a green
  "SAME-DAY DELIVERY" pill; ours: category-image carousel)
- **Bundle Deals and the app banner are CMS blocks and render in English on the
  Arabic storefront.** They need store-scoped Arabic copies, the same fix applied to
  the delivery promise in Finding 6 — a content task, not a theme one.

## Step 8 — search selector, cart badge, utility bar, nav icons, hero pill

### The cart badge already worked

No change needed. It was only invisible because the test cart was empty — with an
item seeded it renders "1" in a 26x24 badge. The earlier "missing badge" reading was
a test-state artefact, not a defect.

### Search category selector — a real filter

`MagentoEgypt\HomeSections\ViewModel\SearchCategories` supplies top-level categories
that are active, in the menu, AND have products (23 here). The control posts as
`cat`, which Magento's catalog search layer reads, so it genuinely scopes results —
verified, not assumed:

| Query | Results |
|---|---|
| `?q=bag` | 10 — Voyage Yoga Bag, Joust Duffle Bag, … |
| `?q=bag&cat=129` (bags) | 12 — Arabic bag products |
| `?q=bag&cat=53` (shoes) | 1 |

Passed as a view model so a failure degrades to a plain search bar instead of taking
down the header — and therefore every page.

### The utility promise was invalid HTML, not a CSS alignment problem

Figma left-aligns the delivery promise; ours rendered centred. The cause was not
`justify-content`:

`.hm-header__promise` was a **`<p>`**, and the CMS block inside it emits its own
`<p>`. A `<p>` inside a `<p>` is invalid, so the browser auto-closed the outer one —
the promise text stopped being a child and became a **sibling flex item** of the
utility row. With three items instead of two, `space-between` put it in the middle.

Measured before the fix: `.hm-header__promise` was **16px wide** — the truck icon
alone. Changed to a `<div>`; it is now 383px and starts at x=16. A globe icon was
added before the language switcher, as Figma shows.

### Category emoji in the nav

Figma's categories are not this store's — it draws Grocery, Pharmacy, FMCG, Kids &
Toys, Cosmetics; this catalogue has clothes, bags, shoes, mobile&tablet, sports. Four
glyphs are Figma's exact choices for concepts that overlap (furniture 🛋️, computer 💻,
home appliances 🏠, clothes→fashion 👗); the rest match its visual vocabulary.

Matched on a normalised, substring-based name so renaming "computer" to "Computers"
does not silently drop the icon, and unknown categories get no emoji rather than a
wrong one. Each is wrapped in `aria-hidden="true"` — otherwise a screen reader reads
"womans clothes clothes".

### Hero kicker as a pill

Figma renders the hero kicker as a filled pill ("SAME-DAY DELIVERY"). Ours now uses
the same treatment with honest content — the live item count. It carries its own
background rather than relying on the scrim, because the kicker sits on
**photography**: a contrast ratio measured against one category image says nothing
about the next one. White on `--hm-success` is a fixed 5.3:1 regardless of the photo.

One trap: `display: inline-block` was not enough. The hero content is a flex column,
whose default `align-items: stretch` overrode it and stretched the pill across the
entire hero. `align-self: flex-start` returns it to its content width.

### Verified

All routes 200, no document overflow at 1440 or 768, contrast 0, no new exceptions.

### Remaining

- The Figma hero uses a marketing headline and subtext ("Fresh Groceries From Local
  Vendors" / "Organic produce, dairy…"); ours is data-driven from the category. That
  is a content decision, not a styling gap — ours shows real catalogue state.
- Utility bar ordering: Figma puts the language switcher FIRST in the right-hand
  group and has a phone icon before "Help"; ours has language last, no phone icon.
- Bundle Deals + app banner still English on `/ar/` (CMS blocks — see task #13).

## Step 9 — emoji font installed, and two bugs it exposed

`fonts-noto-color-emoji` (2.047) installed on the host — one package, no
dependencies, nothing upgraded or removed. Fonts went 147 -> 148. No service
restart needed; fontconfig picks it up and each headless Chrome run is fresh.

> Unrelated: apt reported a **pending kernel update** (running 7.0.0-1009-aws,
> expected 7.0.0-1010-aws). That predates this install and was NOT acted on — no
> reboot was performed.

With the font present the glyphs render, and seeing them immediately exposed two
defects that were invisible while everything was tofu:

### 1. The nav emoji were English-only

The lookup matched the category **display name** (`str_contains($key, 'clothes')`).
On `/ar/` the names are "ملابس", "حقائب", "أحذية" — none contain "clothes", so every
icon silently vanished. **8/8 present in English, 0/8 in Arabic.**

URL keys are not localised on this install (verified: both locales emit
`/clothes.html`, `/bags.html`, `/mobile-tablet.html`), so the match now runs against
the URL, with the name kept as a fallback. Now 8/8 in both locales.

This is the same class of bug as the emoji-in-strings problem avoided earlier —
anything keyed on translated text breaks in the second locale. Worth remembering:
**match on identifiers, not on display text.**

### 2. The search placeholder was untranslated

The header placeholder ("Search products, brands, vendors across all categories…")
was never in `ar_SA.csv` — an older, differently-worded key was. Added, along with
"Search in category" and "All Categories" for the new selector.

### Verified

Nav emoji 8/8 in `en` and `ar`; 12 section headings carry an emoji in both locales;
Arabic renders the translated placeholder, selector and button; layout mirrors
correctly with the selector on the trailing edge. All routes 200, no overflow at
1440 or 768, no new exceptions.

## Step 10 — Arabic CMS blocks

Three homepage CMS blocks rendered English on `/ar/` because their copy is CONTENT,
not translatable strings. Each now has a store-scoped Arabic twin, using the same
pattern as the delivery promise in Finding 6:

| Block | English | Arabic |
|---|---|---|
| `hm_home_app` | 553 (store 3) | 555 (store 1) |
| `hm_home_bundles` | 550 (store 3) | 556 (store 1) |
| `hm_home_hero` (tiles) | 548 (store 3) | 557 (store 1) |

The hero tiles were not in the original request but were the most visible English
left on the page — "Electronics deals / Fashion / Bundle deals" sitting beside a
fully Arabic hero.

Ordering constraint, hit for the second time on this install: **narrow the English
block to store 3 BEFORE creating the Arabic one.** Magento's identifier-uniqueness
check expands store 0 to every store, so the new block is rejected as a duplicate
while the original still sits on All Store Views.

Wording reuses phrases already in `ar_SA.csv` ("تسوق أينما كنت", "عروض الباقات",
"تصفح الباقات") so the CMS copy and the translated UI agree instead of inventing a
second Arabic vocabulary for the same ideas.

### A link bug fixed in passing

`hm_home_bundles` hard-coded `href="/bundles"` with no store prefix — on `/ar/` that
would have dropped the shopper into the English store. Now `{{store url="bundles"}}`
in both copies; verified `/en/` -> `/en/bundles/` and `/ar/` -> `/ar/bundles/`.

### Remaining English on /ar/, and what kind it is

An automated sweep of visible ASCII-only text now returns **no CMS copy at all**.
What is left splits into two categories, neither a theme defect:

- **Product and vendor DATA** — "Savvy Shoulder Tote", "Emma Leggings", "loly",
  "ronza". These are catalogue values needing per-store-view names, i.e. a merchandising
  task.
- **One core label, "Rating:"** — injected client-side by the rating widget, not
  present in the served HTML, so it needs tracking down in JS rather than i18n.

"Regular Price" and "Special Price" WERE core strings missing from the theme's
`ar_SA.csv` and are now translated.

## Step 11 — Arabic: rating label, product names, and every remaining CMS block

### The "Rating:" label was the wrong translation key

`Magento_Review`'s `summary.phtml:22` renders `__('Rating')` and appends the colon as
literal HTML **outside** the translation. The CSV entry was keyed `"Rating:"` with the
colon, so it never matched. Corrected to `"Rating"`.

It then still showed English until the **translate cache** was explicitly cleaned —
`cache:flush` alone had not rebuilt it. Worth remembering: after editing an i18n CSV,
`cache:clean translate` before concluding the key is wrong.

### Product names — 189 translated

The user chose machine translation after being shown the alternatives.

**Approach: translate the product TYPE, keep the model name in Latin.**

| English | Arabic |
|---|---|
| Crown Summit Backpack | حقيبة ظهر Crown Summit |
| Joust Duffle Bag | حقيبة سفر Joust |
| Ryker LumaTech™ Tee (Crew-neck) | تي شيرت Ryker LumaTech برقبة دائرية |

"Joust", "Strive", "Fusion" are product-LINE names, not words. Transliterating them
gives a shopper something unsearchable and unrecognisable; translating them literally
gives nonsense. Translating the noun tells them the one thing that matters — what the
item IS.

Of the 215 shopper-visible English names:

| | Count | Treatment |
|---|---|---|
| Translated | 189 | Arabic type + Latin model |
| Brands | 6 | **Left in Latin** — iPhone 16, Samsung S26 Ultra, Tefal. Translating a brand makes it unfindable. |
| Test products | 19 | **Left alone** — see below |
| Unmatched | 1 | left English |

Applied via `Product\Action::updateAttributes` at store scope, NOT raw SQL, so the
products were marked for reindex — `catalogsearch_fulltext` was rebuilt afterwards, or
search would have kept serving the old English names. Store 0 and store 3 untouched,
so `/en/` is unchanged.

### A finding worth acting on separately

**19 of the visible products are literal test data** — `test item`, `Simple Test`,
`Hungvt Test Product API 1`, `cutom attribue`, `test_2`, `New Bundle Test`. They are
ENABLED and VISIBLE on the live storefront. They were deliberately not given Arabic
names: dressing junk data in Arabic makes it look like real catalogue. They should be
disabled, not translated.

Wider context measured while here: 2,304 products exist but only **326 are enabled and
visible**, and of 2,192 store-1 name overrides only **163 were actually Arabic** before
this change — the rest were English text stored as the Arabic name.

### Every remaining store-0 CMS block now has an Arabic twin

Fixing them one at a time kept revealing the next, so the last eight were done in one
pass: `hm_home_trust`, `hm_home_categories`, `hm_home_promos`, and all six footer
blocks. Combined with the earlier three, **eleven blocks** now have store-scoped
Arabic copies.

**A link bug fixed across all of them:** the footer and category blocks hard-coded
root-relative hrefs (`/customer/account`, `/bundles`, `/clothes.html`). Those have no
store prefix, so on `/ar/` every one of them dropped the shopper into the ENGLISH
store. Both copies now use `{{store url="..."}}`.

### Result

Automated sweep of visible ASCII-only text on `/ar/` went **14 -> 10**, and the ten
remaining are not theme content:

- the clipped `<h1>` (not visible)
- the countdown timer (numeric)
- eight **vendor store names** — "loly", "walmart", "magentoo", "Test2". These are
  names vendors chose for themselves; like brands, they are arguably not translatable
  at all. "Test2" is a test vendor account.

## Step 12 — test products disabled

The 19 products with literal test names that were live and shoppable are now
**status = Disabled** at default scope. Nothing deleted; reversible by setting status
back to 1. Backup: `scratchpad/product-status-pre-disable.sql.gz`.

Applied via `Product\Action::updateAttributes`, not raw SQL, so the products were
marked for reindex — `catalog_category_product` and `catalogsearch_fulltext` were both
rebuilt afterwards. A raw UPDATE would have left them in the indexes and they would
have kept appearing in listings and search.

### Checked before acting, not after

| Product | Finding |
|---|---|
| `test new` (2048) | **has 2 real orders** |
| `test_2` (2292) | configurable with **16 children** |
| `Config Test` (2053) | configurable with 2 children |
| all 19 | **none is a child of another product** |

The orders are unaffected — `sales_order_item` stores its own name and sku, so those
two order lines keep rendering. Nothing upstream breaks because none of the 19 is a
configurable child.

Types disabled: 12 simple, 2 configurable, 3 `new_bundle`, 1 bundle, 1 virtual.

### Result

| | Before | After |
|---|---|---|
| Enabled + visible products | 326 | **307** |
| Visible products named *test* | 19 | **0** |

Search for `test`, `test bundle`, `testing` now returns no results. All routes 200 in
both locales, no new exceptions.

Still worth a look separately: 2,304 products exist but only 307 are enabled and
visible, so the remaining ~2,000 are likely demo or import residue.

## Step 13 — hero banner and side tiles

Measured both sides by anchoring on known text and walking up the ancestor chain,
rather than guessing which element was "the hero".

| | Figma | Ours (before) |
|---|---|---|
| Side tile background | **photo** (`<img>`, `overflow: hidden`) | flat tint, no image |
| Side tile text | **white** on the photo | dark `#1a1a2e` on the tint |
| Side tile radius | 16px | 12px |
| Tile column height | 3 x 145 ≈ hero's 454 | 3 x 130, unrelated to hero |

### Tiles rebuilt as photo cards

Both the English and Arabic CMS blocks now emit an `<img>` per tile using real
category images already on this install (`electronics`, `clothes`, `bags`), referenced
with `{{media url=""}}` so they survive a base-URL change.

Three things this needed beyond swapping in an image:

1. **A scrim, not a measured ratio.** White text sits directly on category
   photography and a merchant can swap that image at any time — a contrast ratio
   measured against today's photo says nothing about tomorrow's. A navy gradient
   guarantees it regardless. Same reasoning as the hero kicker pill.
2. **The scrim direction is a custom property.** CSS gradients take no logical
   keyword, so a physical `to right` would put the dark end on the WRONG side of the
   Arabic tile and strand the text over the bright part of the photo. `--hm-scrim-dir`
   flips under `[dir='rtl']`.
3. **A dark fallback, replacing the old light tints.** If a category image is removed
   or 404s, white-on-`#f5f7fa` is **1.07:1** — completely unreadable. The tiles now
   sit on `--hm-surface-inverse`, so a missing photo degrades to the scrim's own
   treatment instead of an invisible label.

**Height:** the tile column rendered 3 x 225 = 711px against a 399px hero and ran far
past the carousel. `grid-auto-rows: 1fr` divides whatever height the hero sets, so the
two stay aligned at any breakpoint without hard-coding either. Now 402 vs 399.

### The "Previous slide" button had no icon

`hero-carousel.phtml` references `#hm-chevron-left`, but that symbol **was never in
the sprite** — only `hm-chevron-right` existed. The control rendered as an empty white
circle: a live, focusable, labelled button with nothing in it.

Added as a mirror of chevron-right, and every `href="#hm-…"` reference across the theme
and the MagentoEgypt modules was then audited against the sprite — no others are
missing.

### Verified

Tiles: photo present, `radius: 16px`, `overflow: hidden`, white title, navy fallback.
Contrast **0 failing**. Both arrows resolve. No overflow at 1440 or 768. All routes 200
in both locales, RTL scrim mirrored correctly.

### Remaining differences, both deliberate

- **Hero copy.** Figma writes a marketing headline and subtext ("Fresh Groceries From
  Local Vendors" / "Organic produce, dairy…"); ours renders the live category name and
  item count. Ours reflects real catalogue state; Figma's is fixed copy that would go
  stale. Changing this is a content decision, not a styling gap.
- **CTA colour.** Figma's hero button is green; ours is the brand accent orange.
  Matching Figma here would break the token layer that every other CTA on the site
  uses.

## Step 14 — Figma hero copy

The hero previously showed the category name alone ("bags") with an item-count
kicker. It now carries Figma's full anatomy — kicker pill, headline, subtext, and a
category-specific CTA:

| | Figma slide 1 | Ours (bags slide) |
|---|---|---|
| Kicker | Same-Day Delivery | New Season |
| Headline | Fresh Groceries From Local Vendors | Bags For Every Journey |
| Subtext | Organic produce, dairy, and pantry essentials — delivered in hours. | Totes, backpacks and travel bags from verified Egyptian sellers. |
| CTA | Shop Grocery | Shop Bags |

### Configured, not hard-coded

Copy lives in a `slide_copy` layout argument keyed by category **url-key**, so it can
be rewritten without touching PHP. Three reasons for that shape:

1. **URL keys are stable across store views** on this install (both locales emit
   `/clothes.html`, `/bags.html`), so ONE entry serves both languages.
2. **Every string stays translatable.** The template runs each through `__()`, so
   `ar_SA.csv` carries the Arabic — 36 strings added and verified rendering on `/ar/`.
3. **A category with no entry keeps the data-driven fallback** — live item count as
   the kicker, category name as the headline. That matters because slides are selected
   by product count, so which categories appear changes as stock does. Without the
   fallback a newly-qualifying category would render a blank hero.

Nine categories have copy, including `super-market` carrying **Figma's slide-1 text
verbatim** for when that category has stock and images.

### Verified

English and Arabic both render kicker / headline / subtext / CTA per slide, all four
slides distinct. Contrast **0 failing**. Both locales 200.

### Note on the CTA colour

Figma's hero button is green; ours stays brand-accent orange. That is the one hero
difference left, and it is deliberate — the green would break the token layer every
other CTA on the site uses. The `--hm-success` green does appear in the hero, on the
kicker pill, matching Figma there.

## Step 15 — hero CTA to green

Done as a token decision rather than a one-off override, since it is a deliberate
exception to the accent-orange button rule.

- Added `@hm-success-hover: #0c6633` alongside the existing `@hm-success`, exposed as
  `--hm-success-hover`. The design system had a success green but no hover pair.
- `.hm-hero-carousel__cta` now uses `--hm-success` with `--hm-on-status` (white).

Contrast was checked before applying, not after: white on `#0f7b3f` is **5.35:1**, and
on the hover `#0c6633` it is **7.09:1** — so the hover state gains contrast rather than
losing it, which is the usual failure mode when a hover is simply "darker".

**The button component is untouched.** Verified that every other CTA still resolves to
accent orange `rgb(242,101,34)`:

| Control | Background |
|---|---|
| Hero CTA | `rgb(15,123,63)` green |
| Bundle band CTA | `rgb(242,101,34)` |
| Add to Cart | `rgb(242,101,34)` |
| Header search | `rgb(242,101,34)` |

The hero now reads as one unit — the kicker pill directly above the CTA was already
this green, so the two no longer clash.

Contrast **0 failing**, all routes 200 in both locales.

## Step 16 — cross-page check against Figma

Compared PLP, PDP, cart and seller list. The Figma reference is a client-rendered SPA,
so pages had to be reached by CLICKING through it — direct URLs render an empty shell.
(`probe.mjs` also needed fixing to await async results: it was stringifying the Promise
itself, so every navigation probe returned `{}`.)

### A measurement trap, avoided on the third encounter

My first pass reported PLP filter groups and the PDP related-products title as plain
`<strong>` — i.e. not headings. **That was the same error as Finding 1 and Finding 5.**
Magento's layered navigation already ships

```
<strong role="heading" aria-level="2" class="block-subtitle">Shopping Options</strong>
<dt    role="heading" aria-level="3" class="filter-options-title">…</dt>
```

Those ARE headings in the accessibility tree; the probe was printing `tagName`. Checked
before reporting this time. **PLP and PDP heading semantics are correct and need no
work.**

### Real deltas found

**Cart** — two genuine gaps, verified with a seeded cart (an empty cart renders neither
the summary nor the items, so the first check was meaningless):

| Element | Figma | Ours |
|---|---|---|
| Page title | `h1` "Shopping Cart (3 items)" | `h1` "Shopping Cart" — no count |
| Summary panel | `h2` "Order Summary" | `<strong>` "Summary", **no role, no level** |
| Each line item | `h3` product name | `<strong>`, **no role, no level** |

The collapsible blocks beside them ("Estimate Shipping and Tax", "Apply Discount Code",
"More Choices:") DO carry `role="heading" aria-level="2"` — so the summary title and
item names are the outliers, not the pattern.

**PLP** — structurally equivalent. Figma names its filter groups Price Range, Minimum
Rating, Availability and **Vendors**; ours renders whatever attributes are marked
filterable. A vendor filter is marketplace-specific and worth adding, but that is a
catalogue-attribute configuration task, not theming.

**Seller list** — ours has `h1` "Seller List" and no section headings. Figma's
`/vendors` could not be reached by clicking (the link matched a bundle page instead),
so this pair is **unverified**, not confirmed equivalent.

### Not yet compared

Checkout, bundles listing and bundle detail. Checkout in particular needs a seeded cart
or it silently measures the cart page.

## Step 17 — the white gap above the header

**~157px of blank white sat above the header on every page** of the storefront.

Cause: the inline icon sprite. `Magento_Theme::html/icons.phtml` injects a real
`<svg class="hm-sprite">` carrying 40 `<symbol>` definitions into `after.body.start`,
and **nothing ever styled it** — so it rendered at the SVG default intrinsic size,
**300x150**, as the first element in the body and pushed everything down.

It was in every screenshot taken across this entire phase and I read past it each time,
treating the white band as the capture's own padding. The user pointed it out.

### Fix

```less
.hm-sprite { block-size: 0; inline-size: 0; overflow: hidden; position: absolute; visibility: hidden; }
```

Zero-size and absolute rather than `display: none`: hiding a sprite with `display:none`
has historically broken `<use>` references, and this sprite is the icon system for the
entire storefront. So the check that mattered was not "is the gap gone" but **"do the
icons still render"**:

| | Before | After |
|---|---|---|
| `.hm-header` top | **157px** | **0px** |
| `.hm-sprite` box | 300 x 150 | 0 x 0 |
| Sample header icon | 16 x 16 | **16 x 16** — still resolving |

Verified in both locales. Contrast 0, no overflow, all routes 200.

This also means every page was ~157px taller than it needed to be, and every
above-the-fold measurement taken during this phase was shifted down by that amount.

---

## Step 18 — Parity sweep re-run after the sprite fix

The sprite fix moved every page up 157px, which invalidated every above-the-fold
measurement in this phase. Re-ran the full sweep — 66 captures, 11 pages x 3
breakpoints x 2 locales — three times, because the first clean run surfaced two gaps
and fixing them warranted a fresh baseline.

**A false start worth recording.** The first attempt was killed at 4/66: `/en/checkout/`
was redirecting to the cart, meaning checkout would have been silently measured as the
cart page. That is the documented Phase G trap and the reason `seed-cart.mjs` exists —
the guest quote expires, and nothing about the capture looks wrong afterwards. Every
run below re-seeds first and asserts `redirectedToCart: false` before starting.

### Three defects the re-run found

**1. Six PDP images with no `alt`** — Vnecoms' price-comparison "Sold by N other sellers"
table, `Vnecoms_VendorsPriceComparison/web/template/vendor/info.html`. The Knockout
template binds `src`, `width` and `height` but never `alt`.

`alt=""` would have been the wrong fix. The logo is the *only* content of its link (the
vendor name sits in a different cell — the logo cell's `textContent` is empty), so an
empty alt leaves the link with no accessible name, which is worse than a redundant one.
The view model already exposes `getVendorTitle(item)` — the same value the anchor uses
for its `title` — so the fix binds that:

```html
attr:{src: getLogoUrl(item), ..., alt: getVendorTitle(item)}
```

**2. One node still on Open Sans** — `.cms-content-important` on `/about-us`.
`Magento_PageBuilder`'s `_module.less` hardcodes `font-family: 'Open Sans', ...` as a
literal rather than through `@font-family-name__base`, so overriding the theme's font
variables could never reach it. Fixed in `_hm-cms.less` via the usual collector order
(`_extend` loads after `_module`, so equal specificity wins).

**3. Checkout had zero live regions.** This one was hidden by a bad metric on my part:
the earlier gate summed `aria-live` nodes site-wide and read 66, which looks like full
coverage but is just a total. Counting *captures with at least one* gave 60/66 — and the
six were all of checkout, both locales, every breakpoint.

The cause is structural: checkout uses the minimal header, so it never inherits the
minicart counter's `aria-live` that silently covers every other page. Totals recalculate
on shipping and payment changes with no announcement at all.

Fixed by overriding `Magento_Checkout/web/template/summary/totals.html` to add
`aria-live="polite" aria-atomic="true"` to the totals table. Scoped to the **table**, not
`.opc-block-summary`, because the summary block also holds the item list — a live region
there re-announces every product name on each recalculation. `aria-atomic` because
subtotal, shipping, tax and grand total move together and hearing only the changed row is
more confusing than hearing all four.

A verification note: the Arabic checkout first probed as *still* having no live region.
That was a cold-cache timing race — the probe ran before Knockout finished rendering
after a `cache:clean` — not a real gap. Re-probed with the summary rendered, both locales
carry the attributes.

### Final gates — run m5

| Gate | m3 | m4 | m5 | Want |
|---|---|---|---|---|
| Captures | 66 | 66 | 66 | 66 |
| Overflow at any breakpoint | 0 | 0 | 0 | 0 |
| Contrast failures | 0 | 0 | 0 | 0 |
| Captures with `h1` != 1 | 0 | 0 | 0 | 0 |
| Images without `alt` | 12 | 0 | 0 | 0 |
| Captures with an `aria-live` region | 60 | 60 | **66** | 66 |
| Captures with a skip link | 66 | 66 | 66 | 66 |
| Wrong `dir` for locale | 0 | 0 | 0 | 0 |
| Type under 12px | 0 | 0 | 0 | 0 |
| Off-family fonts | 1 | 0 | 0 | 0 |

**All ten gates pass.** Raw captures in `docs/design/parity/runs-m5/`.

---

## Step 19 — Cart heading semantics, and the last four page comparisons

### Cart headings

Measured the live DOM before changing anything, which immediately corrected the
backlog note. "Estimate Shipping and Tax", "Apply Discount Code" and "More Choices:"
**already** ship `role="heading" aria-level="2"` — the same trap that produced three
wrong findings earlier in this phase. Only one heading was genuinely missing.

| | Before | After |
|---|---|---|
| Summary title | `<strong class="summary title">Summary</strong>` | `<h2>Order Summary</h2>` |
| Cart line item name | `<strong class="product-item-name">` | same + `role="heading" aria-level="3"` |
| Page heading | `Shopping Cart` | `Shopping Cart (1 item)` / `سلة التسوق (١ منتج)` |

Three decisions worth recording:

**The summary title needed no template override.** Core renders it through
`Magento_Theme::text.phtml`, which already takes the tag as a layout argument — so the
whole fix is four lines of `checkout_cart_index.xml`. The `summary title` class is kept
so existing CSS still applies. "Order Summary" matches Figma and already had an Arabic
translation (`ملخص الطلب`) in VendorExtend's `ar_SA.csv`.

**Item names kept `<strong>` and gained ARIA rather than becoming real `<h3>`.** A
census found product names are `<strong class="product-item-name">` in **all 98
instances site-wide**; swapping the tag on the cart alone would have broken every
`strong.product-item-name` rule and made the cart the one inconsistent page.
`role="heading"` is also the pattern core already uses here.

Note the crosssell block's four product names were deliberately **not** promoted —
Figma's cart has no crosssell section, and its item-name `h3`s are the cart lines.

**The count is server-side, and safe to be.** Both preconditions were verified before
writing the code: the cart page is uncacheable (`cacheable="false"`), and
`Theme\Block\Html\Title` declares no cache lifetime, so the heading is not block-cached
either. A stale count would be worse than no count. The count comes from
`Checkout\Helper\Cart::getSummaryCount()` — the same source as the minicart badge — so
heading and badge cannot disagree.

New view-model-only module `MagentoEgypt_CheckoutExtend` (no `setup_version`, so no
`setup:upgrade` and no DbStatusValidator risk). The title template is bound to the cart
handle alone rather than overriding `html/title.phtml`, which renders the `<h1>` of every
page on the storefront.

Arabic numerals go through `NumberFormatter`, so the heading reads `(١ منتج)` rather than
a Latin `1` inside Arabic text — matching prices and the minicart badge. Plurals use two
forms (one/other), not Arabic's four: Magento's i18n has no plural categories, and core's
own Arabic pack ships exactly this split. `(١١ منتجات)` is therefore strictly `منتجًا` in
formal MSA — consistent with the rest of the store rather than novel.

### An outage, and the cause

The site went fully down for ~25 minutes mid-step. Symptom set was misleading: every PHP
route timing out, **idle** CPU, **idle** MySQL (3 connections), fpm children in state `S`,
and `AH01075 ... (polling)` in the Apache log. Static assets served 200 throughout, which
is what isolated it to php-fpm.

Cause: `di:compile` wipes and regenerates `generated/`, and this pool runs
`opcache.validate_timestamps = 0`. A graceful `systemctl reload` keeps existing children
alive holding bytecode and autoload maps that point into the tree that was just deleted —
with timestamp validation off they never notice, and they stop answering.
`systemctl restart` restored service in seconds.

**The deploy sequence for any PHP change is now: `di:compile` → static deploy →
`systemctl restart php8.4-fpm` → cache clean.** Two 7-day-old orphaned `php-fpm: master`
processes were found while diagnosing; they long predate the outage and were not the
cause. hub-market runs on the **`www` pool** (`pm.max_children = 5`), not `mtwonew`.

### Cross-page comparison — checkout, bundles, bundle detail, seller directory

One real bug, found by comparing rather than assuming:

**`/en/checkout/` rendered its heading in Arabic.** The page was correctly `lang=en
dir=ltr`, but the h1 read `الدفع`. Because the translation layer was English, the string
could not be coming from `__()` — it was a config value:
`amasty_checkout/general/title` held Arabic **at default scope**, so every store view
inherited it. Re-scoped: default `Checkout`, store 1 `الدفع`. Backed up first.

Heading sizes were compared at 1440. Figma does not use one global `h1` size and neither
do we — they simply did not line up:

| Page | Figma | Was | Now |
|---|---|---|---|
| Cart | 30px | 36px | **30px** |
| Checkout | 30px | 48px | **30px** |
| Product | 36px | 32px | **36px** |
| Bundle detail | 30px | 32px | **30px** |
| Seller directory | 48px | 36px | **48px** |
| Bundles listing | 36px | 36px | 36px ✓ |

Figma deliberately sizes a bundle's detail page a step below an ordinary product; Magento
serves both through `catalog-product-view`, so `page-product-bundle` is what separates
them.

**Two selectors were written against markup that does not exist here**, both compiling
cleanly and matching nothing — worth recording as a repeat failure mode:
Amasty renders its own `h1.title` inside `.checkout-header`, not
`.page-title-wrapper > .page-title`; and the bundles landing page opens with a
CMS-authored `h1.hm-bundle-hero__title`, with the core title block suppressed. Verifying
by computed size, not by "the CSS is in the file", is what caught both.

Also closed:
- **Bundles listing missing its 🎁.** Added via `::before` with the existing
  `.hm-section-emoji` mixin, so the glyph stays out of the `<title>` tag and both store
  views get it without a second Arabic string.
- **`/ar/bundles` served the English page.** The `bundles` CMS page was store 0. Arabic
  copy created on store 1 with the hero translated; the `{{widget}}` directive was carried
  across by targeted replacement rather than retyping, since its backtick/`^[ ]` escapes do
  not survive hand-copying.

All 16 route/locale combinations return 200; cart heading outline verified in both locales.

### Left open deliberately

- **Button radius.** Ours is a uniform 6px; Figma's is mixed (0, 12, and pill). Changing
  the global radius token would touch every button on the site — surfacing rather than
  deciding it here.
- **Seller directory title wording.** Ours reads "Seller List", Figma's "Featured Sellers
  & Brands". That is store copy, not styling.

---

## Step 20 — Button radius adopted from Figma (12px)

Task 17 was filed as "ours 6px vs Figma's mixed 0/12/pill". The first measurement was
too crude to act on, so it was re-taken before changing anything: restricting the census
to elements that actually **paint a background** and carry their own label — i.e. where a
radius is visible — resolved the mixture completely.

| Figma element | Radius |
|---|---|
| Real CTAs — "Shop Grocery", "Add Bundle" | **12px** |
| Filter chips — "All Bundles", "🛒 Grocery" | pill |
| App-store badges | 16px |
| "Search" submit, "All" category select | 0 |

The `0`s are the two halves of one composite search field, not standalone buttons, and the
pills are chips. So 12px is *the* button radius, not one option among three — which is
what the earlier "mixed" reading had suggested.

### What changed

The theme already had 12px on its scale as `@hm-radius` (`--hm-radius`); buttons were
simply pointing at `@hm-radius-sm` (6px). So this is a re-pointing, not a new value.

- **`@button__border-radius: @hm-radius`** in `_theme.less` — the blank-framework
  variable, which reaches every core `.action` and `button` at once.
- **19 explicit button rules** moved from `var(--hm-radius-sm)` to `var(--hm-radius)`,
  selected from the 39 uses of the small token: `.hm-btn`, `.action.tocart`,
  `.action.toquote`, the PageBuilder buttons, the checkout CTAs, `.hm-hero-carousel__cta`
  and `.hm-app__cta`.
- **`@hm-radius-sm` deliberately kept** for inputs, textareas, selects, chips, the OTP
  toggle, skeletons and images. Figma does not round those like buttons, and collapsing
  `sm` into the base value would have flattened the scale rather than fixed anything.

### Two things the verification caught

**The newsletter Subscribe button stayed at 3px through two deploys.** Blank hardcodes
that value, and matching its `.block.newsletter .action.subscribe` (0,3,0) was not enough:
blank declares it inside `.media-width('min', @screen__m)`, which Magento emits into
**styles-l.css** — a separate sheet loaded after styles-m.css, with the desktop rules at
the very end. An unconditional `_extend` rule compiles into styles-l.css too, but near the
top, so at equal specificity blank still won on order. Locating both rules by byte offset
in the built file is what showed this; the fix was the `.hm-page` body class, taking it to
(0,4,0) and making it independent of sheet order.

Worth noting blank rounds it that way because *its* newsletter is a joined field. Ours is
not — measured live, there is a 4px gap between the input's right edge and the button's
left — so it is simply a button and takes the button radius.

**The search category select was rounded on the edge it butts against.** Its rule already
used logical properties correctly, setting only `border-start-start-radius` and
`border-end-start-radius`. But a bare `select` rule in the form styles sets the
`border-radius` **shorthand**, so the two corners the theme did not name stayed at 6px —
putting a notch mid-band. Fixed by squaring the end pair explicitly. The band now reads as
one segmented control, and mirrors correctly:

| | Cat select | Input | Button |
|---|---|---|---|
| LTR | `6/0/0/6` | square | `0/6/6/0` |
| RTL | `0/6/6/0` | square | `6/0/0/6` |

For the record, Figma's own search field is square throughout — our 6px outer rounding is a
pre-existing choice, left alone as outside this task.

### Verification

Buttons measured 12px on every page in both locales — 45 controls on the homepage alone.
The only non-12 controls remaining are the skip link (visually hidden until focused) and
the two halves of the search field, which match Figma's `0`.

A regression sweep was run because a global radius change touches every component. **All
ten gates unchanged from the pre-change baseline** (m5 → m6): zero overflow, zero contrast
failures, exactly one `h1` per capture, aria-live and skip link on all 66, no off-family
fonts. Raw captures in `docs/design/parity/runs-m6/`.

---

## Step 21 — Seller directory retitled to the Figma copy

Figma's `/vendors` heading is **"Featured Sellers & Brands"**; ours read "Seller List"
(`قائمة البائعين`). Adopted, in both locales.

### No PHP needed — but only because it was measured first

The heading does **not** come from the layout argument it looks like it comes from.
Vnecoms declares the string in three places, and the `<h1>` is fed by the third:

| Source | Feeds |
|---|---|
| `sellerlist/view/frontend/layout/default.xml:14` | a nav label |
| `sellerlist_index_index.xml:16` | a block `title` argument |
| `Controller/Index/Index.php:42` — `getConfig()->getTitle()->set(__('Seller List'))` | the `<h1>` **and** `<title>` |

Overriding the layout argument would have changed nothing visible. Since the controller
routes the string through `__()`, an i18n entry restrings it without touching PHP or
overriding a controller — the standard Magento way to customise a third-party string.

Before doing that, a text-node scan across the storefront confirmed the string surfaces in
exactly **two** places: the `<title>` tag and the `<h1>`, both on `/sellerlist`. The nav
label in Vnecoms' `default.xml` never renders here (our footer uses custom CMS blocks), so
restringing globally has no collateral effect. Theme `i18n/` sits above module `i18n/` in
Magento's translation hierarchy, so these override VendorExtend's pack:

```
i18n/en_US.csv   "Seller List","Featured Sellers & Brands"
i18n/ar_SA.csv   "Seller List","أبرز البائعين والعلامات التجارية"
i18n/ar_EG.csv   (same — VendorExtend ships ar_EG too)
```

Arabic joins with the conjunction `و` rather than an ampersand, which is how the
construction is actually written.

**Figma's `<br>` was not reproduced.** Its markup is `Featured Sellers<br>& Brands` — a
presentational break for its 48px landing headline. A `<br>` inside a translated string
would be escaped to a literal `&lt;br&gt;` by `escapeHtml`, and hardcoding a break point
into copy does not survive translation into Arabic. The heading wraps naturally instead.

### Verification

`<title>` and `<h1>` both updated in both locales, and no horizontal overflow at 1440 /
768 / narrow — the existing responsive rule drops the heading to 28px on small screens,
which absorbs the longer Arabic string (33 characters against the English 25). All ten
route/locale combinations still 200. `en_US.csv` contains exactly one line, so nothing
else on the storefront was restrung.

### Remaining delta on this page

Figma places a subtitle under the heading — "Browse verified vendors across every
industry — electronics, fashion, groceries, cosmetics". We go straight from the `<h1>` to
the seller-search block. Not added here, since this task was scoped to the title and a
subtitle is new store copy in two languages. There is a ready-made hook if it is wanted:
Vnecoms already reads `vendors/sellerlist/top_static_block` from config (currently empty),
so it is a CMS block plus one config value, no code.

### Step 21b — the subtitle

Added, closing the last delta noted above. Copy lives in a CMS block
(`hm_sellerlist_subtitle`, one store-scoped row each for English and Arabic) so a
merchandiser can edit it without a deploy.

**Vnecoms' own hook was the wrong tool.** `vendors/sellerlist/top_static_block` exists and
is empty, and was the path suggested when this was filed — but its block sits in `content`
with `before="-"`, while the module `<move>`s `page.main.title` down into
`sellers.container`. The hook therefore renders *above* the heading. Placed explicitly
instead.

**Declaring the block with `after="page.main.title"` also failed** — it rendered at the
very bottom of the container, below the seller search, measured at y=1123 against the
search's y=279. At the point a block declaration is resolved, `page.main.title` is still in
`content` and the module's `<move>` has not run, so the anchor does not exist and the block
falls through to the end. The fix is to declare it plainly and issue our own `<move>`:
move directives are applied after all blocks are placed, and in document order, so ours
runs after theirs and the anchor is there. Order is now h1 → subtitle → search in both
locales at every breakpoint.

Styling matches the reference — 16px / 400 / 26px line-height — with two deliberate
departures:

- **Colour** uses `--hm-foreground-muted` (`#6b7280`) rather than Figma's `#6b6560`. The
  two are visually indistinguishable; ours is already the palette token and already
  contrast-checked, and adding a near-duplicate grey would trip the off-palette gate in the
  sweep for no visible gain.
- **Measure** is `41ch`, not Figma's fixed `448px`. Stating the cap in the font's own
  metrics gives 449px in DM Sans (the reference measure, matched) and 394px in IBM Plex
  Sans Arabic, whose zero glyph is narrower — the same number of character widths in both,
  which is what a measure is for. A hard 448px would have given the Arabic face a longer
  character count than the Latin one. The first pass used 52ch (569px), a visibly longer
  line than the design.

No horizontal overflow at 1440 / 768 / narrow in either locale; all route/locale
combinations still 200.

---

## Step 22 — Logged-in account area and the auth-gated marketplace routes

The one item that had been blocked all phase: these pages need a signed-in session, and
the parity sweep runs as a guest, so **nothing here had ever been measured**. Six real
defects were sitting behind the login.

The session belongs to a real customer with real order and address data. Everything below
was read-only — no form submitted, no setting changed, no order touched — and only layout
and style metrics were extracted, never personal content.

### What was already fine

`.box-actions` now has `gap: 16px`; the "Edit / Change Password run together as one string"
defect recorded when this phase was planned was fixed by `_hm-account.less` in step 2b.
Fonts across the account area are DM Sans + Playfair Display only. All ten account routes
return 200 in both locales, and every core Magento account title is properly translated
into Arabic.

### 1. Order history scrolled the whole page sideways

`/sales/order/history/` had `scrollWidth` 1820 against a 1536 viewport. The cause was the
pager, and it is a Luma idiom misfiring:

```css
.account .toolbar .pages { position: absolute; width: 100%; z-index: 0 }   /* blank, styles-l.css */
```

That works in Luma because the pager's static position is its container's left edge, so
`100%` lands exactly on it. Here the toolbar is flex and the pager sits as a child at
x=754 inside a 354px box — so `width: 100%` resolved against the 1066px toolbar but painted
from 754, reaching 1820. Returned to the flow with `position: static; width: auto`.

The first attempt at this used `.hm-page .account .toolbar .pages` and changed nothing:
`hm-page` and `account` are **both classes on `<body>`**, so the descendant combinator asks
for an `.account` inside an `.hm-page` and matches nothing. It needs to be compound —
`.hm-page.account`.

### 2. Account headings were never brought onto the type scale

`_hm-account.less` had no page-title rule at all, so "My Account", "My Orders" and
"Address Book" fell through to blank's **48px** — the loudest headings on the storefront,
on its most utilitarian screens. Set to 30px, matching the cart and checkout. Figma's
account screen uses 24px, but its heading is the customer's *name* inside one tabbed page;
ours are per-route page titles, and going below the cart would add a fifth size to the
scale for no gain.

### 3. Two-column layouts did not mirror in Arabic

Measured on `/ar/customer/account/`: sidebar at x=56, main at x=370 — **pixel-identical to
`/en/`**. Arabic customers got a left-hand sidebar. Blank positions these columns with
physical floats (`float: left` / `float: right`), and `float` has no logical behaviour.

Fixed by making only the direction logical — `float: inline-start` / `inline-end` — leaving
blank's float layout otherwise intact.

A first attempt turned `.columns` into a flex container instead, which looked ideal because
blank already ships `order: 1/2` on these columns and they are inert while `.columns` is
`display: block`. It failed: blank's flex model also sets `flex-basis: 100%` on both columns
as a mobile default, and `flex-basis` outranks `width`, so the desktop 16.67% / 83.33%
widths were ignored and the columns stacked full-width.

**This was not only an account bug.** `page-layout-2columns-left` is also the category-page
layout, so the Arabic PLP had the same unmirrored sidebar — on guest pages that the sweep
covers. The sweep missed it because its direction gate checks computed `direction`, not
physical layout. Verified after the fix on `/clothes.html` in both locales: side by side and
correctly mirrored at 1440 and 768, stacked at 500, no overflow anywhere.

### 4. Two contrast failures and the last off-family font, on the RMA route

`Vnecoms_Rma` ships a plain `rma.css` — not LESS, so no theme variable reaches it — which
hardcodes `font-family: Arial,sans-serif` and white text on light fills. Measured on
`/vrma/customer/index`:

| Chip | Before | After |
|---|---|---|
| "Refund" — white on `#ffa800` | **1.93:1** | **5.02:1** |
| "Resolved" — white on `#60a653` | **2.97:1** | **5.35:1** |

Repainted with the theme's status tokens, which are already contrast-checked against
`--hm-on-status`. Arial was the last off-family font anywhere on the storefront.

### 5. Three untranslated or awkward Arabic titles

Checking every Arabic account route systematically rather than one at a time showed core
Magento is fully translated and only the Vnecoms routes are not:

| Route | Before | After |
|---|---|---|
| `/ar/vstorecredit` | "My Credit" (English) | `رصيدي` |
| `/ar/quotation` | "Quotes Request" (English) | `طلبات عروض الأسعار` |
| `/ar/vrma/customer/index` | `طلب طلب الاسترجاع` — "طلب" doubled | `طلب استرجاع` |

All three route through `__()`, so theme `i18n/` entries cover them; being a frontend
theme, the admin strings those same phrases feed are untouched.

### Verification

Regression sweep after the RTL change (which now reaches guest category pages): **all ten
gates unchanged**, with one exception that is not a regression — `checkout-ar-1440` reported
no `aria-live` region. Re-probed directly and it is present (`polite` / `atomic`); this is
the cold-cache Knockout race documented in Step 18, on the same page, after caches were
cleared immediately before the run.

Raw captures in `docs/design/parity/runs-m7/`.
