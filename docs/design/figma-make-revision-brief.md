# MECommerce — Figma Make revision brief

Paste-ready instructions for the Figma Make chat on
`https://www.figma.com/make/JUOBqx9pO5eJn2EYVQPcwr/Multi-Vendor-Marketplace-UI-UX`.

Derived from a six-dimension audit of the published build (`https://doze-coyote-58038022.figma.site/`)
— 130 KB compiled CSS + 460 KB React bundle — producing **155 findings** (41 P0 / 84 P1 / 30 P2).
Full detail: [01-findings-register.md](audit/01-findings-register.md).

## How to use this

Paste **one numbered prompt at a time**, in order, and let the build settle between each. Prompts 1–4
are blocking defects; 5–8 are quality; 9 is additive scope. Prompts marked ⚠️ are large enough that
they should be split further if the model starts truncating.

Target stack for context — tell Make this once: *"This design will be implemented as a Magento 2.4.8
theme for a Gulf multi-vendor marketplace (Vnecoms) serving UAE, KSA, Qatar, Bahrain and Kuwait, with
live English (LTR) and Arabic (RTL) store views."*

---

## Corrections applied

The audit was automated; these headline claims were checked directly against the bundle and **changed**
before being written into the prompts below. Recorded so the register is read with the right caveats.

| Claim as reported | What the bundle actually shows |
|---|---|
| "Tailwind blue-600 `#155dfc` is the de-facto primary" | That hex appears **0 times**. Blue arrives as `bg-blue-600` etc. resolved through oklch — **93 blue utilities + 12 indigo/violet/purple**. Off-brand pollution is real; the evidence was wrong. |
| "Six pages contain zero brand colour" | Overstated. Brand colours are used heavily: `#f26522` ×138, `#c85c2c` ×95, `#0f2144` ×72. |
| "Zero logical CSS anywhere" | False. Tailwind v4 already emits `margin-inline`/`padding-inline` (**32 occurrences**). The real gap is *directional* utilities (~45), **0** `rtl:` variants, **0** `[dir=rtl]` rules, **0** `<bdi>`. |
| "`font-synthesis` is disabled, bold renders as semibold" | No `font-synthesis` declaration exists. Browsers will **faux-bold** instead — still a defect, different mechanism. |
| "Currency hard-coded in 35 places" | **16** `toFixed(2)` calls. `Intl.NumberFormat` is used **0** times — that part holds. |
| "65 physical direction utilities" | **~45** (`left-N` 15, `right-N` 16, `ml-auto` 5, `border-r` 6, `border-l` 1, `text-left` 2). |
| "47 distinct emoji" | **51** distinct emoji code points. |
| "Focus outlines repainted to 10% black, no focus anywhere" | **36** `:focus-visible` rules do exist. The defect is that `--ring: #0003` renders them at ~1.6:1 — present but invisible. |

Independently verified and **confirmed**: `aria-label` **0**, `aria-live` **0**, `htmlFor` **0**,
`sr-only` **1**; `Intl.NumberFormat` **0**; `<bdi>` **0**; DM Sans requested at **300;400;500;600 only**
while `font-bold`/`font-extrabold` is used **136 times**.

---

## 1 — Replace the token layer ⚠️

> Replace the entire contents of `src/styles/theme.css` with the stylesheet below, then remove every
> `oklch()` colour block that remains — those are shadcn dark-mode defaults that were never part of this
> brand, and they are what created the second, competing neutral ramp.
>
> Then refactor components so colour comes **only** from these tokens. Today the token layer is
> effectively dead code: of roughly 390 colour utilities in the app, almost none resolve to a declared
> token. Replace every hard-coded hex and every raw Tailwind palette utility (`blue-600`, `gray-400`,
> `red-500`, `green-600`, `amber-400`, `indigo-*`, `violet-*`, `purple-*`) with the semantic token that
> matches its role.
>
> [paste the full contents of `docs/design/tokens.css` here]
>
> Key rules that must survive the refactor:
> - `--accent` (#f26522) is a **fill** colour. It must never carry text on white — it is 3.15:1.
> - Text labels on an accent fill use `--on-accent` (navy #0f2144, 5.04:1). **Not white** — white on
>   #f26522 is 3.15:1 and fails.
> - Orange **text** on light surfaces uses `--accent-strong` (#c2410c, 5.18:1). Prices use this.
> - Do not darken `--accent-hover` past #e85d18; below that the navy label drops under 4.5:1.

## 2 — Accessibility P0 ⚠️

> Fix these, in this order:
>
> 1. **Give every control an accessible name.** The bundle contains `aria-label` 0 times, `sr-only`
>    once, `htmlFor` 0 times. Add `aria-label` to all 19+ icon-only buttons (wishlist, cart, quantity
>    steppers, carousel arrows, slide dots, search submit, close buttons).
> 2. **Associate every form control with its label.** 28 `<label>` elements exist with no `htmlFor`, and
>    no input has an `id`. Add matching `id`/`htmlFor` pairs throughout, including checkout.
> 3. **Make focus visible.** 36 `:focus-visible` rules exist but paint `--ring: #0003` at ~1.6:1.
>    Switch to `--focus-ring` (#c2410c, 5.18:1) on light surfaces and `--focus-ring-inverse` (white) on
>    navy, at 2px with a 2px offset. Restore outlines on the site search, mobile search and header
>    category select, which currently remove theirs with no replacement.
> 4. **Remove hover-only affordances.** Add-to-cart and wishlist on the product grid are `opacity-0`
>    until hover, so they do not exist on touch. Make them always visible, or reveal on
>    `:focus-within` as well as hover.
> 5. **Add live regions.** `aria-live` is used 0 times, so add-to-cart, filter changes and search
>    results are silent. Add a polite live region for cart and result-count changes.
> 6. **Fix the dark "Picked For You" band** — its text runs 1.54:1 to 3.76:1. Every layer must clear
>    4.5:1 (use `--on-inverse` / `--on-inverse-muted`).
> 7. **Fix footer contrast** — legal links and copyright sit at ~2.2:1 on navy, the lowest in the build.
> 8. **Raise sub-14px type.** 188 of 475 sized declarations are below 14px, some as low as 7px. Set a
>    12px floor for decorative text and 14px for anything carrying commerce data.
> 9. **Guard motion.** `prefers-reduced-motion` appears 0 times while three infinite animations and 12
>    hover-scale transforms run unconditionally.
> 10. **Fix touch targets.** At least 14 interactive controls compute under 44px; four are under the
>     WCAG 2.2 floor of 24px. Carousel slide dots are 6×6px.
> 11. **Give the rating a text alternative.** Stars are 1.35:1 filled-vs-empty. Always render the
>     numeric value beside them, and use `--rating-star` (#d97706).
> 12. **Stop nesting `<button>` inside `<a>`** on the product card — it is invalid HTML and breaks
>     keyboard and screen-reader behaviour.
> 13. **One `<h1>` per page, stable.** The homepage `<h1>` is currently the carousel headline and
>     changes every 4.5 seconds; the Search page has no `<h1>` at all.

## 3 — Arabic and RTL P0 ⚠️

> This design ships to a live Arabic store view. Today the language toggle only flips `dir` and `lang` —
> there is no translation layer, and 100% of copy stays English.
>
> 1. **Add an Arabic typeface.** Neither loaded font contains a single Arabic glyph — DM Sans ships
>    `latin`/`latin-ext` only, Playfair Display ships `latin`/`latin-ext`/`cyrillic`/`vietnamese`. Load
>    **IBM Plex Sans Arabic** (it carries both `arabic` and `latin` subsets, so numerals and Latin
>    fragments inside Arabic text stay in one family) and apply it via the `:lang(ar), [dir="rtl"]`
>    block in the new token sheet.
> 2. **Remove the 45 inline `fontFamily` style objects** — they make the Arabic swap impossible from a
>    stylesheet.
> 3. **Stop the logo mirroring.** It is two flex siblings, so RTL reverses "MECommerce" to
>    "**Commerce**ME". Lock it with `dir="ltr"` and treat it as a single indivisible wordmark.
> 4. **Add bidi isolation.** There is no `<bdi>`, no `dir` attribute and no isolation mark anywhere. Wrap
>    every LTR-locked value that can appear inside Arabic text — prices, SKUs, order IDs, phone numbers,
>    URLs, Latin brand names — in `<bdi>`. This is what currently produces `...Search products` with a
>    leading ellipsis and `.fashion — one marketplace` with a leading full stop.
> 5. **Mirror directional things.** ~45 physical utilities remain: `left-N` (15), `right-N` (16),
>    `ml-auto` (5), `border-r` (6), `border-l` (1), `text-left` (2). Convert to logical equivalents
>    (`start`/`end`, `ms-auto`, `border-e`, `text-start`). Specifically:
>    - carousel prev/next are pinned `left-3`/`right-3` with non-mirroring chevrons
>    - product-card badges are pinned physically, so the discount badge and wishlist heart never swap
>    - the quantity stepper uses `border-r` on minus and `border-l` on plus, so in RTL both dividers
>      move outward and the middle cell loses its separators
>    - search icons and select carets are absolutely positioned physically
>    - the hero scrim is `bg-gradient-to-r`, so in RTL the right-aligned white text lands on the bright
>      side of the photograph
> 6. **Fix the discount badge.** It is built as `["-", pct, "%"]`, so in RTL the minus detaches and
>    `-25%` renders as `25%-`. Compose it as a single pre-formatted string.
> 7. **Replace `←`/`→` literals** in pagination and the bundle back-link — Unicode does not bidi-mirror
>    those characters. Use mirroring icon components.
> 8. **Drop letter-spacing and uppercase in Arabic.** 52 `tracking-*` utilities and 42 `uppercase`
>    transforms are destructive (letter-spacing breaks cursive joining) or meaningless in Arabic.
> 9. **Raise Arabic line-height.** Heading line-heights of 1.0–1.25 plus fixed min-heights on clamped
>    titles will clip Arabic ascenders, descenders and diacritics. Use the `--leading-arabic-*` tokens.
> 10. **Add real Arabic copy** to Home, Category, Product and Checkout so Arabic typography can actually
>     be judged. Two Arabic strings currently render inside a `lang="en"` document with no `lang` of
>     their own.
> 11. **Persist the language choice** — it is component state only, so it resets to English LTR on every
>     reload and cannot be linked to.

## 4 — Currency and locale P0

> 1. **Every price is `$`.** Replace with **AED** as the default, formatted via `Intl.NumberFormat`
>    (used 0 times today) with the store locale and currency, not string concatenation.
> 2. **`toFixed(2)` is wrong for this region.** It is used 16 times. Kuwaiti dinar and Bahraini dinar
>    use **three** decimal places. Let `Intl.NumberFormat` decide precision per currency.
> 3. **Remove the hard-coded `Tax (10%)` line** in the cart. None of the five target countries uses 10%
>    (UAE/KSA/Bahrain/Oman VAT differ; Kuwait and Qatar have none). Label it VAT and drive it from data.
> 4. **Reconcile the free-shipping threshold** — three different values in two currencies across four
>    components, against a header promising "AED 150".
> 5. **Pass a locale to `toLocaleDateString()` / `toLocaleString()`** — they are called bare, so dates
>    and thousands separators follow the visitor's browser rather than the store view.
> 6. **Add `font-variant-numeric: tabular-nums`** to money columns in cart and checkout so decimals
>    align.
> 7. **Replace the US address form in checkout** — it has `+1 (555)` phone, State/ZIP, New York/NY/10001
>    placeholders and **no Country field at all**. Use a Gulf address model (country, emirate/city,
>    area/district, street, building, landmark) and Gulf payment methods (Mada, Tap, Tabby, STC Pay,
>    cash on delivery) to match the footer's own claims.

## 5 — Consolidate components ⚠️

> The build has no shared component layer for its most repeated elements. Collapse each of these into
> one component with declared variants:
>
> | Element | Distinct renderings today |
> |---|---|
> | Product card | **12** (only one is a component; one variant is dead code) |
> | Vendor / seller card | **9** |
> | Star rating | **11** inline loops, 4 sizes, 3 colours, 2 always render 5/5 |
> | Price treatment | **11** current-price + **8** was-price |
> | Badge | ~**20** ad-hoc treatments |
> | Button | **74** raw `<button>`s — 7 primary fills, 3 hover oranges, 10 icon sizes |
> | Section header | ~**12** configurations, 7 different "see all" labels |
>
> Also: use the shadcn Tabs, Select, Pagination, Breadcrumb, Progress and Carousel already compiled into
> the bundle instead of the hand-rolled versions, and delete the unused shadcn/Radix surface — 634 of
> 1,356 compiled selectors are dead, and the Magento build must not carry them.

## 6 — Brand cohesion

> 1. **Remove the third and fourth palettes.** 93 `blue-*` utilities and 12 `indigo/violet/purple`
>    utilities are in use — most visibly the "Picked For You" AI band, which is a self-contained
>    indigo/violet mini-palette. Restate it in `--primary` + `--accent`.
> 2. **Collapse the five near-duplicate oranges** (including `#c85c2c`, used 95 times and never
>    declared) into `--accent` / `--accent-hover` / `--accent-strong`.
> 3. **Collapse the eight undeclared navy/near-black shades** into `--primary` and `--neutral-900`.
> 4. **Replace the 8 Material pastel category tiles and 4 pale section bands** — 16 colours from a
>    foreign design system — with `--surface-muted`.
> 5. **Retire the warm neutral ramp** (`#f6f4f1`, `#ede9e3`, `#c9c4bc`, `#9e9890`) in favour of the cool
>    one. `#9e9890` is 2.86:1 on white and fails even the non-text threshold.
> 6. **Unify the three visual systems.** Home is dense-commercial, Vendors is editorial-luxury, and the
>    checkout funnel is unbranded; the Platform page runs two at once. Converge on the commercial
>    system and keep at most one editorial moment (the seller-directory hero).
> 7. **Delete the `.dark` block** — it is pure shadcn greyscale with no brand in it, and the shell
>    advertises `color-scheme: light dark` while no `prefers-color-scheme` rules exist.

## 7 — Replace the emoji icon system

> **51 distinct emoji code points** are doing icon duty — in the category nav, section headings, footer
> links, bundle filter chips, and as functional glyphs (`✓`, `★`, `♡`). Emoji render differently per
> OS, cannot be recoloured or optically aligned, are announced literally by screen readers ("heavy
> black heart"), and several are direction-bearing or contain Latin text and so cannot be localised.
>
> Replace all of them with the lucide icon set already in the bundle (47 lucide icons are present, so
> two parallel icon systems currently express the same concepts), on a 24px grid at one stroke weight.
> Where an emoji is decorative, mark it `aria-hidden`; where it is functional, give it a named icon and
> an accessible label.
>
> **Emit them as a single inline SVG sprite** referenced with `<use href="#icon-name">`, not as
> individual files and not as an icon font. That is what the Magento theme will consume, so matching it
> here keeps design and code on the same system. It also means icons inherit `currentColor` (so they
> recolour from the token layer), mirror correctly in RTL, and cost no extra request.
>
> Name each symbol semantically (`icon-cart`, `icon-vendor-verified`, `icon-delivery`), not visually
> (`icon-truck`), so the sprite survives a redesign.

## 8 — Missing states and quality

> Add the states that do not exist anywhere in the bundle: **loading / skeleton, empty, error,
> out-of-stock, disabled, no-results**. Then:
> - lazy-load the 132 remote images and give them intrinsic dimensions and responsive sources
> - unpin `html{font-size:16px}` so the user's browser font-size preference is respected
> - make type responsive above 36px — only 6 of 475 size declarations are responsive, and 48px/128px
>   headings are unguarded on mobile
> - raise form controls to 16px so iOS does not zoom on focus
> - add a skip link, make the category bar a `<nav>`, and give the breadcrumb real markup instead of
>   `div`s with literal `/` spans
> - fix the Search page: its sticky search bar is `z-10` under a `z-50` sticky header at the same offset
> - make header search actually pass its query — it is currently discarded, and the search page ignores
>   the URL
> - fix the broken links: the Electronics category tile targets a non-existent slug, and **6 of 10
>   vendor cards** link to "Vendor not found"
> - give the hero carousel a pause control
> - **verify mobile** — I could not check it, because the published site renders at a fixed desktop
>   width in an iframe. Check every page in Make's own device preview.

## 9 — Add the missing marketplace screens ⚠️

> The design covers 14 pages; a Vnecoms marketplace needs considerably more. Add:
>
> **Auth & seller** — customer login/register (with the **Email ↔ Mobile OTP tab toggle** the live
> storefront uses), forgot password, seller registration, seller login, seller dashboard. Today Login
> and Register both link to `/profile` and no auth page exists; every "Start Selling" CTA dead-ends on
> a marketing page.
>
> **PDP** — an **"Other Sellers" offer list** (the defining marketplace feature, currently absent), a
> review list and review-submission form, a specification table, and a real variant model. The current
> one mixes configurable-product stock with custom-option price deltas and renders two controls for the
> same attribute.
>
> **PLP** — per-page control and a working pager (both currently decoration), plus layered navigation
> that matches what Magento can actually do.
>
> **Vendor** — per-vendor Shipping and Refund policy tabs (currently baked into hardcoded prose), and
> the vendor microsite at `/shop/<url-key>` rather than `/vendor/<numeric-id>`.
>
> **Cart** — discount-code field, shipping estimator, update-cart, move-to-wishlist.
>
> **Checkout** — an **order-success page**; "Place Order" is currently a dead button.
>
> **Account** — the account is one `/profile` route with four client-side tabs. Magento has eight-plus
> separate routes; add order history (with the Vnecoms per-vendor order split), addresses, returns/RMA,
> store credit, wishlist and downloadables.
>
> **Also** — wishlist page, product compare, newsletter, contact, advanced search, and CMS pages for the
> ten footer destinations that currently all resolve to `/about`.
>
> ### These are the priority — nothing downstream can be styled without them
>
> Every screen below already exists as a live Magento route on this install and is currently undesigned.
> They will be left on default Magento styling — visibly unfinished next to the rest of the storefront —
> until this design covers them. Please draw each one:
>
> | Screen | Live route |
> |---|---|
> | Seller registration | `/marketplace/seller/register` |
> | Seller login (with mobile-OTP tab) | `/marketplace/seller/login` |
> | Seller dashboard + seller nav | `/marketplace/dashboard` |
> | Vendor About / Shipping / Refund tabs | `/shop/<vendor>/...` |
> | "Other sellers" offer list on PDP | `pricecomparison` block on PDP |
> | Review list + review submission form | PDP + `/review/product/list` |
> | Order history with per-vendor split | `/sales/order/history` |
> | Returns / RMA | `/vrma` |
> | Store credit | `/vstorecredit` |
> | Quotation / RFQ | `/quotation` |
> | Product compare | `/catalog/product_compare` |
> | Wishlist page | `/wishlist` |
> | Address book + address edit | `/customer/address` |
> | Advanced search | `/catalogsearch/advanced` |
> | Checkout success | `/checkout/onepage/success` |
> | 404 | `no-route` |
>
> For each, the states that must be drawn as well: **empty, loading, error**. Those do not exist
> anywhere in the current design, and Magento renders all three natively.
>
> Note the account section: the design collapses everything into one `/profile` route with four
> client-side tabs. Magento serves eight-plus separate URLs, each a full page load. Draw it as a
> sidebar-plus-page layout, not as tabs.

---

## Decisions only a human can make

1. **Is the brand orange fixed?** The accessible route keeps `#f26522` as a fill with navy labels. If
   marketing requires white-on-orange buttons, the orange must move to `#c2410c` and the brand shifts
   noticeably deeper. *Recommendation: keep `#f26522`, use navy labels.*
2. **Serif or not?** Playfair Display is currently on 100% of h1/h2/h3, including 12–14px product-card
   titles, and on the PDP price. It also has no Arabic counterpart, so the two locales diverge
   structurally. *Recommendation: Playfair for hero and section headings only; DM Sans for product
   titles, prices and all UI.*
3. **Does the "AI Engine / Picked For You" feature exist?** It promises per-user reasoned
   recommendations with no engine behind it. Keep, downgrade to "Recommended for you", or cut.
4. **Which currency and countries at launch?** The design mixes `$`, "AED 150" and five countries.
5. **Do the vendor data points exist?** Vendor cards depend on five fields Vnecoms does not provide out
   of the box (per-vendor delivery time, verification badge, response rate, etc.).
6. **Is the seller panel in scope?** The footer advertises "Seller Dashboard" and "Platform Panels" —
   that is a separate Magento area with its own theme.

## Magento implications to carry into the theme build

- **Checkout is Amasty One Step Checkout Pro**, but the design draws a 3-step wizard. Restyle Amasty
  rather than rebuild — and note the design renders checkout inside the full 4-band header and footer,
  which Magento's checkout layout deliberately strips.
- **Never collect raw card number/CVV in the merchant DOM**, as the checkout mock does. Payment fields
  must be hosted-fields/iframe from the PSP. This is a PCI-DSS scope question, not a design preference.
- **Route collisions:** 4 of 14 designed pages have no native Magento route, and 3 collide with Magento
  URL conventions. Vendor microsites are `/shop/<url-key>`; the seller directory is `/sellerlist`.
- **The global reset restyles bare `h1`–`h4` and zeroes borders** — dropped into Magento it will re-skin
  core markup it was never designed for. Scope it.
- **`--radius` drift:** `rounded-xl` and `rounded-2xl` both resolve to 1rem.
- **Bundles:** the bundle page is a fixed read-only kit with no option groups, so the project's custom
  `new_bundle` type has nowhere to render selections.
