# Prompt 9 — Add the missing marketplace screens ⚠️

Paste the block below into the Figma Make chat. This one is large — split it if the model truncates.

---


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
