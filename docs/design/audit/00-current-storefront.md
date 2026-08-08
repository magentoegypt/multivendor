# Current storefront — "before" reference

Captured 2026-08-05 from `https://hub-market.magento2.click/` via a browser session.
Theme in use: **`Mgs/supro`** (theme_id 7, parent `Mgs/blank` → `Magento/blank`).

## Access

The storefront returns **403 from the application server itself**, but loads normally from a
developer browser. All visual verification for this project therefore runs through the browser
session, not server-side fetches. This is the cross-verification channel for every later phase.

## Store views

| ID | Code | Name | State |
|---|---|---|---|
| 1 | `ar` | عربي | **Default** — real content, RTL |
| 3 | `en` | English | **Homepage is empty** (see below) |
| 2 | `vendors` | Vendor Panel | separate `vendors` Magento area |

The site resolves to Arabic/RTL by default at the bare domain.

## Observed state

### Arabic (`/`) — RTL, populated
- Deep navy header, logo (MAGENTO EGYPT · "Multi-Vendors Marketplace") pinned right, icon rail left
  (settings, orders, cart, wishlist, account).
- Search with a "جميع الفئات" category select; below it a second navy bar with a hamburger
  "جميع الفئات" trigger — the `HubMarket_DynamicMenu` category nav.
- Hero carousel renders, cream background.
- Floating WhatsApp button (green, off-palette) bottom-right; floating cart button bottom-left in the
  **old coral accent `#f68872`**.

### English (`/en/`) — LTR, effectively empty
- Header mirrors correctly (logo left, icon rail right).
- **"Featured Products" renders `--`. "Featured Categories" renders `--`.** The English homepage has
  no content — the Fbuilder homepage was only populated for the Arabic store view.
- Category select label truncates to "All Catego".

## Defects visible in the current build

| # | Defect | Note |
|---|---|---|
| 1 | **Missing icon glyphs** — `` boxes render in place of the carousel prev/next arrows | Linearicons icon font not resolving for those codepoints |
| 2 | **English homepage empty** | `--` placeholders where product/category lists should be |
| 3 | Category select label truncated ("All Catego") | fixed-width select |
| 4 | Floating cart uses the retired accent `#f68872` | design moves to `#f26522` |
| 5 | WhatsApp button is raw brand green, unstyled against the palette | |
| 6 | `design/head/includes` injects `{{MEDIA_URL}}styles.css`, which **404s on every page** | dead request, remove |
| 7 | `mgs_theme.less.bak-pre-owlfix` was deployed into `pub/static` | stray backup served publicly |

## Implication for rollout

Because the **English storefront homepage has no content to lose**, assigning the new
`MagentoEgypt/hub-market` theme to the `en` store view first carries close to zero risk. The Arabic
store view — which holds all the real content and traffic — stays on `Mgs/supro` until RTL parity is
verified.
