# MECommerce design audit — full findings register

Generated from a six-dimension automated audit of the published Figma Make build
(`https://doze-coyote-58038022.figma.site/`) — compiled CSS (130 KB) + React bundle (460 KB).

**155 findings.** Load-bearing P0 claims were spot-verified directly against the bundle; where
verification changed a claim it is marked in `docs/design/figma-make-revision-brief.md` under
"Corrections applied". Findings here are as reported by the audit and are *not* individually
verified — treat counts as indicative unless corrected in the brief.

Severity split: P0 41 · P1 84 · P2 30


## P0

### `arabic-toggle-with-no-arabic-typography` — The RTL toggle flips dir/lang at runtime but the design ships zero Arabic typography — Arabic headings would fall through Playfair → Georgia → OS serif

**Evidence.** Header utility bar: `g = () => { const p = !i; n(p), document.documentElement.dir = p ? "rtl" : "ltr", document.documentElement.lang = p ? "ar" : "en"; }` with the button label `children: i ? "English" : "عربي"`. make.css has 0 occurrences of `[dir=`, `:dir(`, `rtl`, and 0 `@font-face` rules. The only two Arabic strings in the whole 460 KB bundle are `"عربي"` and `"التحويل إلى العربية"` (the toggle labels). The app shell hardcodes the Latin stack inline: `r("div", { className: "min-h-screen bg-[#f5f7fa]", style: { fontFamily: "'DM Sans', system-ui, sans-serif" }, ...`. Meanwhile the copy promises it 10 times: `"Arabic & RTL Ready"`, `"Full Arabic translation"`, `"RTL layout support"`, `"Full Arabic-language interface with right-to-left layout..."`.

**Impact.** Pressing the toggle mirrors the layout while every string stays English — that is precisely what produces the observed "CommerceME" logo and the leading-ellipsis "...Search products" placeholder: they are Unicode bidi artifacts of Latin text under dir=rtl, not a translation. When real Arabic arrives, body text falls DM Sans → `system-ui`, and every h1/h2/h3 falls Playfair Display → `Georgia` → generic `serif`. Georgia has no Arabic, so Arabic headings render in the OS default Arabic serif: Traditional Arabic/Times on Windows, Geeza Pro on iOS/macOS, Noto Naskh on Android. Three different heading typefaces for the same page across three devices, none of them chosen.

**Fix.** Ship a real Arabic pairing bound to `:lang(ar), [dir="rtl"]`. Recommended: **IBM Plex Sans Arabic** (SIL OFL, weights 100–700, humanist-geometric — the closest tonal and x-height match to DM Sans) for all body/UI, and for the display role that Playfair holds in Latin either **Amiri** (high-contrast Naskh revival, the true typographic counterpart to a Didone) or **Noto Naskh Arabic**. For an e-commerce storefront the safer call is to drop the display serif in Arabic entirely and use IBM Plex Sans Arabic 600/700 for headings. Declare both faces with `unicode-range: U+0600-06FF, U+0750-077F, U+08A0-08FF, U+FB50-FDFF, U+FE70-FEFF` so Latin pages never download them. Remove the inline `fontFamily` from the app-shell div so a stylesheet can override per-locale.

**Magento.** Two store views (en / ar_SA). Magento does not resolve locale-specific CSS from `web/i18n/`, so put the Arabic faces + `:lang(ar)` overrides in a `_typography-ar.less` imported by an `ar_SA`-scoped child theme, or gate on the `<html lang="ar" dir="rtl">` that `Magento\Framework\Locale` already emits. Vnecoms vendor-submitted product names will be mixed ar/en in the same field — the Arabic face must be first in the stack with DM Sans as the Latin fallback, not the reverse.

### `auth-pages-absent-no-otp` — Login and Register both link to /profile; there is no auth page and no Email/Mobile OTP tab toggle

**Evidence.** make.js:5612-5613 `t(C, { to: "/profile", ..., children: "Login" }), t(C, { to: "/profile", ..., children: "Register" })`. `grep -ciE 'log ?out|sign ?out' make.js` = 0. `grep -c 'Verification' make.js` = 0, `grep -c 'guest' make.js` = 0.

**Impact.** The one storefront flow that was custom-built and hardened for this project (Vnecoms SMS OTP with the Email-vs-Mobile tab) has no design. There is also no logout affordance anywhere in the entire bundle, and no logged-in vs logged-out header state — /profile renders John Doe's data unconditionally.

**Fix.** Design `customer/account/login` with the two-tab toggle (Email+password | Mobile+OTP), the OTP code-entry state, resend timer, and error states; `customer/account/create`; forgot-password; and a logged-in header account dropdown containing Logout. Also design the guest-vs-login gate at the top of checkout.

**Magento.** Per the project's SMS login notes the mobile tab drives OTP and the email tab uses core login — the tab component is the load-bearing piece and must be drawn, not inferred.

### `buttons-nested-inside-anchors` — Every product card nests `<button>` elements inside the `<a>` that wraps the whole card

**Evidence.** make.js `function tt({ product: e, variant: a = "default" })` — all three variants return `r(C, { to: `/product/${e.id}`, className: "group block bg-white rounded-xl ...", children: [ ... t("button", { className: "w-9 h-9 bg-[#f26522] ...", onClick: (n) => n.preventDefault(), ... }) ] })`, where `C` is react-router `Link` and renders `O("a", {...href...})`. The compact variant nests `t("button",{className:"w-6 h-6 bg-[#f26522] ..."})` the same way.

**Impact.** `<a>` has an interactive content model that forbids nested interactive descendants. Browsers repair the DOM unpredictably, screen readers flatten the card into one giant link whose accessible name becomes the concatenation of vendor name + product name + review count + both prices, and the nested buttons may be dropped from the accessibility tree or reported as part of the link. The only thing keeping navigation from firing is `onClick: (n) => n.preventDefault()`, which is a behavioural patch over a structural defect.

**Fix.** Restructure the card: the wrapping element becomes a plain `<div>` (or `<article>`), and only the product title becomes the `<a>`, with `::after{position:absolute;inset:0}` giving the whole card a click target. The quick-add and wishlist buttons then sit as siblings with `position:relative;z-index:1`. This also collapses the link's accessible name down to just the product name.

### `c85c2c-text-fails-aa` — #c85c2c — the second most-used brand colour (95 occurrences) — fails AA as text at 4.18:1, and is not covered by the agreed #c2410c fix

**Evidence.** `.text-\[\#c85c2c\]{color:#c85c2c}` with 35 `text-[#c85c2c]` uses in make.js. Contrast on #fff = **4.18:1** (AA needs 4.5:1 for text under 18.66px bold / 24px regular). Size distribution of those 35: `text-[10px]` ×12, `text-[9px]` ×1, `text-xs` ×1, `text-sm` ×2 — every measurable one is small text. Examples: `t("span",{className:"text-[10px] tracking-widest uppercase text-[#c85c2c] border border-[#c85c2c]/40 px-2 py-0.5",children:"Verified"})` (10152); `{icon:Ht,text:"Free shipping on orders over $50",color:"text-[#c85c2c]"}` (8297). It is also used as a fill with white label text — `"text-xs tracking-widest uppercase text-white bg-[#c85c2c] px-2 py-1"` (8184, PDP 'Save X%') and `"group block bg-[#c85c2c] border border-[#c85c2c] hover:bg-[#b54e24]"` (10299) — where white-on-#c85c2c is also **4.18:1**. On the warm surface #f6f4f1 it drops to **3.81:1**.

**Impact.** The established remediation only names #f26522→#c2410c. #c85c2c is a *different* colour (Δrgb 44 from #f26522), is used 95 times, and independently fails. If only #f26522 is fixed, roughly a third of the accent text on Product/Vendors/Features/Platform stays non-compliant — including the 'Verified' trust badge and the 'Free shipping' promise.

**Fix.** Fold #c85c2c into the single accent token and apply the same rule as #f26522: #c2410c (5.18:1) for text/links/borders, the vivid orange for fills only — and when used as a fill, pair it with a foreground that clears 4.5:1 (white on #c2410c is 5.18:1, so `bg-accent-strong text-white` works). Delete #c85c2c, #d9561d, #b54e24 and #f9884a as separate values.

**Magento.** In `_theme.less` define two variables, `@accent__color` (fills, #f26522) and `@accent__color__text` (#c2410c), and never let a `.phtml` use the fill colour on text. Add a stylelint/LESS lint rule if possible — this class of regression reappears every time a new template is added.

### `checkout-address-form-is-us-locale` — Checkout shipping form is a US address form — `+1 (555)` phone, State/ZIP fields, New York/NY/10001 placeholders, and no Country field at all

**Evidence.** Checkout component `vc` (make.js ~334,900–341,000), verbatim field list:
`children: "Phone"` / `type: "tel"` / `placeholder: "+1 (555) 000-0000"`
`children: "Address"` / `placeholder: "123 Main Street"`
`children: "City"` / `placeholder: "New York"`
`children: "State"` / `placeholder: "NY"`
`children: "ZIP Code"` / `placeholder: "10001"`
`children: "Email"` / `placeholder: "john@example.com"`
`children: "Card Number"` / `placeholder: "1234 5678 9012 3456"`
No `Country` label or select exists anywhere in the component. Review step hard-codes `"John Doe"`.

**Impact.** For a marketplace that ships to UAE/KSA/Qatar/Bahrain/Kuwait this is unusable: the UAE has no postal-code system at all, Qatar has none either, KSA uses a 5+4 national address code, Bahrain uses 3–4 digit block numbers, Kuwait uses 5 digits. "State" has no meaning in the UAE (Emirate) or KSA (Region/Province). The `+1 (555)` prompt tells a Gulf shopper to enter a US number, and there is no country selector to drive VAT, shipping zone or currency.

**Fix.** Rebuild the shipping step for the Gulf: add a required Country select (UAE/KSA/Qatar/Bahrain/Kuwait) as the first field; make the region field a dependent select (Emirate for AE, Region for SA, Municipality for QA/BH/KW) labelled from the country; make postcode optional and hidden for AE/QA; phone field split into a country-code select defaulting from the country (+971/+966/+974/+973/+965) plus a national-number input with `dir="ltr"` and `inputmode="tel"`. Replace all placeholders with local examples (e.g. Address `"Villa 12, Street 7, Jumeirah 1"`, City `"Dubai"`).

**Magento.** Magento already models this: enable only the five Gulf countries in `general/country/allow`, set `general/region/state_required` per country, and supply `directory_country_region` rows for AE Emirates / SA Regions (Magento ships none for AE by default — a data patch is required). Do NOT let the design's flat State/ZIP inputs drive the checkout layout; map to Magento's `customer_address` address-format per country instead.

### `checkout-form-us-shaped` — The checkout form is a US address form — wrong fields, wrong placeholders, no country, no Gulf payment methods

**Evidence.** Checkout `vc` (8962) field labels: `"First Name"`, `"Last Name"`, `"Email"`, `"Phone"`, `"Address"`, `"City"`, `"State"`, `"ZIP Code"` — no Country field at all. Placeholders: `placeholder: "+1 (555) 000-0000"`, `placeholder: "123 Main Street"`, `placeholder: "New York"`, `placeholder: "NY"`, `placeholder: "10001"`. The review step hard-codes `"John Doe"`, `"123 Main Street"`, `"New York, NY 10001"` (9190-9196), and CustomerProfile saved addresses repeat `city: "New York, NY 10001", phone: "+1 (555) 000-0000"` (9745-9757). Payment is card-only — `"Card Number"`, `"Cardholder Name"`, `"Expiry Date"`, `"CVV"` — while the footer advertises `["Visa", "Mastercard", "Mada", "Tap", "Tabby", "STC Pay"]` (5744) and the trust row promises `"Tap, Visa, Tabby BNPL"` (7402). Totals: `s = n * 0.1` labelled `"Tax (10%)"` (8969, 8919) — neither the UAE/Bahrain 5% nor the KSA 15% VAT rate. Free-shipping logic `s = l > 50 ? 0 : 9.99` (8804) and the PDP promise `"Free shipping on orders over $50"` (8297) both contradict the header's `"Free delivery on orders over AED 150"` (5606) and the bundle page's `"Free delivery over AED 150"` (10944). Checkout also drops the per-vendor grouping that Cart establishes (`Object.entries(d)` at 8828) — the checkout item list is flat.

**Impact.** The single highest-value flow is designed for the wrong market. There is no emirate/region selector, no country, no Arabic-name field handling, and the only payment method drawn is a raw card form — so Mada, Tabby BNPL and STC Pay have no UI at all despite being promised twice. A 10% tax line will show a wrong total in every one of the five launch countries.

**Fix.** Instruct Figma Make to rebuild the checkout form for the GCC: Country select (UAE/KSA/Qatar/Bahrain/Kuwait) driving a dependent Emirate/Region select, no ZIP for UAE, `+971`/`+966` phone patterns, Arabic-capable name fields. Replace the card-only payment step with a payment-method chooser (Card, Mada, Tap, Tabby, STC Pay, Cash on Delivery) each with its own panel. Make VAT a labelled line whose rate comes from the store, not a literal 10%. Carry the per-vendor grouping from Cart into Checkout and Review.

**Magento.** Magento's `directory` tables already model country + region for all five markets and `Magento_Tax` computes VAT per store view — so the design just needs to stop hard-coding 10%. Vnecoms splits an order into per-vendor sub-orders at `Vnecoms\VendorsSales`; the checkout review screen must show that split or customers will not understand multi-vendor shipping and returns. Tabby/Tap/STC Pay are third-party payment modules that render their own method rows in the `Magento_Checkout` payment step — the design must leave room for them.

### `checkout-labels-not-associated` — No form control in the build is programmatically labelled — `htmlFor` occurs 0 times, and no input has an `id`

**Evidence.** `grep -c htmlFor make.js` = 0. Checkout renders 12 fields in this exact shape: `t("label", { className: "block text-sm font-medium mb-2", children: "Card Number" })` followed by a sibling `t("input", { type: "text", placeholder: "1234 5678 9012 3456", className: "w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" })`. Same pattern for First Name, Last Name, Email, Phone, Address, City, State, ZIP Code, Cardholder Name, Expiry Date, CVV. The PDP variant picker repeats it: `t("label", { className: "text-xs tracking-widest uppercase text-[#0a0a0a] font-medium", children: E.name })` beside an un-`id`ed `<select>`.

**Impact.** The labels are visual only. A screen reader announces "edit blank" for every checkout field, and the placeholder disappears on first keystroke so there is no persistent name. This is a payment form: a blind user cannot reliably distinguish Expiry Date from CVV. Fails WCAG 1.3.1, 3.3.2 and 4.1.2. The filter sidebar is the one exception — its checkboxes and radios are wrapped by `<label>`, so those are fine.

**Fix.** Give every input an `id` and every label a matching `htmlFor`. Alternatively wrap, as the filter sidebar already does. Do this for all 31 `<input>`, 6 `<select>` and the `<input type="range">`.

**Magento.** Magento's own `Magento_Checkout` knockout templates already emit `<label for="…">`; the risk is a designer-supplied override .phtml copying this Figma Make structure verbatim. Enforce the for/id pairing in the checkout .phtml templates and add a `bin/magento dev:tests` smoke assertion.

### `checkout-no-country-no-shipping-method` — Checkout address form has no Country field and there is no shipping-method selection anywhere

**Evidence.** `grep -c 'Country' make.js` = 0 and `grep -c 'Shipping Method' make.js` = 0. The address form (make.js:9006-9095) is `"First Name"`, `"Last Name"`, `"Email"`, `"Phone"`, `"Address"`, `"City"`, `"State"`, `"ZIP Code"` with placeholders `"New York"`, `"NY"`, `"10001"`, `"+1 (555) 000-0000"`. Step 1 goes straight to `onClick: () => a("payment")`.

**Impact.** Breaks a marketplace whose own header promises `"Delivering across UAE, KSA, Qatar, Bahrain & Kuwait"`. Without country_id Magento cannot resolve tax rate, shipping rates, region list or postcode optionality, and the quote cannot be saved. Without a shipping-method step the order literally cannot be placed — Magento rejects a quote with no shipping method selected.

**Fix.** Add a required Country select (defaulting from the store view), make Region a country-driven select/text swap, mark postcode optional for UAE/Qatar/Kuwait, and localise placeholders. Insert a shipping-method section listing carrier + method + rate + delivery estimate, with a per-vendor grouping (see vendor-split finding).

**Magento.** Postcode optionality is `general/country/optional_zip_countries`; region behaviour is `general/region/display_all`. Magento 2.4.8 ships regions for KSA and UAE, so 'State' should be a select for those two and free text elsewhere.

### `checkout-raw-pan-fields` — Checkout collects raw card number / CVV in the merchant's own DOM

**Evidence.** make.js:9106-9150: `placeholder: "1234 5678 9012 3456"` labelled `children: "Card Number"`, plus `children: "Cardholder Name"`, `children: "Expiry Date"` `placeholder: "MM/YY"`, `children: "CVV"` `placeholder: "123"` — all plain `<input type="text">` with no iframe or gateway component.

**Impact.** Unimplementable as drawn without pulling the whole storefront into PCI DSS SAQ A-EP/D scope. Every gateway the design itself advertises in the footer (`"Visa", "Mastercard", "Mada", "Tap", "Tabby", "STC Pay"`) is redirect- or hosted-field-based, so none of them will render into these inputs.

**Fix.** Replace the hand-drawn card form with a payment-method radio list where each selected method exposes its own gateway-rendered region (hosted iframe / redirect notice / BNPL widget). Design the list states, not the card fields. Add Cash on Delivery as a first-class option — `grep -c 'Cash on Delivery' = 0` and it is the dominant Gulf method.

**Magento.** Magento payment methods each render their own KO component under `Magento_Checkout/js/view/payment/list`; the theme styles the wrapper, never the PAN inputs. Tabby/Tamara additionally need a PDP and cart instalment widget, which the design also lacks.

### `checkout-wizard-vs-amasty-osc` — Checkout is drawn as a 3-step wizard, but the stack ships Amasty One Step Checkout Pro

**Evidence.** make.js:8963 `const [e, a] = z("info")` driving `e === "info"` / `e === "payment"` / `e === "review"` blocks, with a stepper rendering `children: "Shipping Info"`, `children: "Payment"`, `children: "Review"` and buttons `children: "Continue to Payment"` / `children: "Review Order"`.

**Impact.** The single most expensive conflict in the design. Amasty OSC renders address, shipping method, payment and order review simultaneously in a 2-3 column single page; there is no step state, no 'Continue to Payment' transition, and no separate Review screen to style. Building the wizard means disabling OSC (losing what was paid for) or rewriting Amasty's KO components and layout XML.

**Fix.** Redraw checkout as one page: left column = login/guest + shipping address + shipping method + payment method + optional delivery date; right column = sticky order summary with editable qty, coupon field and place-order button. Keep the 3-circle progress bar only if it is a passive scroll-position indicator, not a gate.

**Magento.** If the wizard is genuinely required, the cheaper path is to drop Amasty and use core Magento's 2-step checkout (Shipping -> Review & Payments), which already matches steps 1 and 2+3 of the design. Do not build a custom 3-step flow.

### `currency-is-dollar-everywhere-no-intl-formatting` — Prices are hard-coded `"$"` + `.toFixed(2)` in 35 places with zero Intl formatting, contradicting the site's own "AED 150" promise — and `toFixed(2)` is wrong for BHD/KWD

**Evidence.** 35 bare `"$"` literals rendered as JSX siblings, e.g. `r("span", { className: "text-sm font-bold text-[#0f2144]", children: ["$", e.price] })`, plus template forms `` `$${s.toFixed(2)}` ``. 16 × `.toFixed(2)`. Distribution: Layout/cards 10, Cart 6, Checkout 5, CustomerProfile 4, Home 3, Platform 3, Product 2, Category 2. Also literal copy `"Free shipping on orders over $50"` (Product), `"Save $200"`, `"$0"` / `"Up to $"` on the Category price slider, `"$2,459.87"` and `"$163.99"` on CustomerProfile.
Directly contradicted by 9 AED strings: `"Free delivery on orders over AED 150  ·  Delivering across UAE, KSA, Qatar, Bahrain & Kuwait"` (header), `"Same-day delivery · Free over AED 150"` (Home), `"AED 41,200"` / `"AED 8,240"` / `"AED 12,450"` (Platform), `"Free delivery over AED 150"` (Bundles).
`grep -o 'Intl\.[A-Za-z]*' make.js` → 0 matches.
Correct ICU output for reference: `ar-AE` → `‏1,299.50 د.إ.‏`; `ar-SA` → `‏١٬٢٩٩٫٥٠ ر.س.‏`; `ar-BH` → `‏١٬٢٩٩٫٥٠٠ د.ب.‏`; `ar-KW` → `‏١٬٢٩٩٫٥٠٠ د.ك.‏`; `en-AE` → `AED 1,299.50`.

**Impact.** Three separate defects. (1) Wrong currency on every price in a Gulf marketplace. (2) BHD and KWD are three-decimal currencies — `toFixed(2)` silently truncates fils, so cart subtotal/tax/total arithmetic and the price column width are both wrong for Bahrain and Kuwait. (3) Because there is no Intl layer, the Arabic store view cannot render Arabic-Indic digits, the Arabic decimal separator (U+066B) or the RLM-wrapped symbol placement that ar-SA/ar-QA/ar-BH/ar-KW expect.

**Fix.** Delete every `"$"` literal and every `.toFixed(2)`. Introduce one `formatPrice(amount, currency, locale)` helper backed by `Intl.NumberFormat(locale, {style:'currency', currency})` and route all 35 sites through it, letting ICU decide symbol position, digit count and numbering system. Decide and state a single canonical currency per country (AED/SAR/QAR/BHD/KWD) and make the free-delivery threshold copy read from the same source as the price formatter so `AED 150` and the cart threshold can never diverge again. Wrap the rendered price in `<bdi>`.

**Magento.** Magento handles this natively via per-website currency + `Magento\Framework\Pricing\PriceCurrencyInterface`; never render a currency symbol in a .phtml. Set currency precision per store — Magento's `directory/currency` handles the 3-decimal BHD/KWD case, but any custom bundle/discount maths in MagentoEgypt modules must not assume 2dp.

### `dark-ai-section-contrast` — The dark "Picked For You" band runs 9–12px text at 1.54:1 to 3.76:1 — every text layer in it fails

**Evidence.** Section background `style: { background: "linear-gradient(135deg, #080d1a 0%, #0f1b35 40%, #12103a 100%)" }`. Measured against the lightest stop #12103a: `className: "text-white/40 text-xs"` (subtitle) = 3.76:1; `"text-[10px] text-white/35 mb-0.5"` (vendor name) = 3.16:1; `"text-[9px] text-white/35 ml-1"` (review count) = 3.16:1; `"text-[10px] text-white/30 line-through ml-1.5"` (was-price) = 2.64:1; `"text-[10px] text-white/20 flex items-center gap-1.5"` = 1.84:1; empty rating stars `"text-white/15"` = 1.54:1; the indigo icon `"w-3 h-3 text-[#6366f1]/60"` = 2.25:1. The disabled Refresh button (`"... text-white/60 ... disabled:opacity-40"`) computes to white/24 = 2.12:1.

**Impact.** Normal text needs 4.5:1 and non-text UI needs 3:1. Nothing in this band except the pure-white product title and price passes. The was-price at 2.64:1 and the footnote at 1.84:1 are functionally invisible to anyone over about 45. Fails 1.4.3 and 1.4.11.

**Fix.** Raise the alpha floor for text on this gradient: nothing below `text-white/70` for body copy (7.99:1 at /70 on #12103a) and nothing below `text-white/55` for secondary meta. Replace `text-white/15` empty stars with `text-white/35` outlined stars. Raise the minimum font size in this band from 9px/10px to 12px. Drop `#6366f1` here — it is off-brand and unreadable on this background.

### `dm-sans-bold-never-loaded` — DM Sans 700/800 are used 136 times but never requested, and font-synthesis is disabled — every bold price silently renders as semibold

**Evidence.** make.css line 1: `@import"https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap"` — DM Sans is requested at 300;400;500;600 only. make.js uses `font-bold` 124× and `font-extrabold` 12×; `.font-bold{font-weight:var(--font-weight-bold)}` with `--font-weight-bold:700` and `--font-weight-extrabold:800`. make.html ships `@layer figoverridable{:root{font-synthesis:none}}` (font-synthesis is an inherited property, so this applies to the whole tree). By element tag, bold/extrabold sits on div ×85, span ×17, p ×8, button ×2 — 112 non-heading (i.e. DM Sans) usages. 8 of the 12 `font-extrabold` uses are prices: `"text-lg font-extrabold text-[#0f2144]"`, `"text-4xl font-extrabold text-[#0f2144]"`, `"text-xl font-extrabold text-[#0f2144]"`, `"text-base font-extrabold text-[#0f2144]"`, `"text-sm font-extrabold text-white"`, `"text-sm font-extrabold text-[#f26522]"`.

**Impact.** With no 700/800 face loaded and synthesis switched off, the browser falls back to the nearest available weight — 600. So `font-bold` and `font-extrabold` render identically to `font-semibold`. The price emphasis the design relies on does not exist in the build: on a product card the price (`text-lg font-extrabold`) is the same weight as the vendor label (`font-semibold`) beside it. The whole weight hierarchy collapses from four steps to three.

**Fix.** Change the import to `DM+Sans:wght@400;500;600;700` (add 700; drop 300, see separate finding). Then either remove every `font-extrabold` and use 700 as the top weight, or add `800` to the request as well. Verify visually that bold ≠ semibold after the change — right now they are pixel-identical.

**Magento.** Self-host the DM Sans weights under `web/fonts/` and declare them in `_typography.less` with `font-display: swap`; never carry `font-synthesis:none` across, and never rely on faux-bold in Magento either since the same trap applies to the Arabic face.

### `five-different-primary-cta-fills` — Five different primary-button fills in a single purchase funnel, with five different hover colours

**Evidence.** Header search: `"bg-[#f26522] hover:bg-[#d9561d] text-white px-6 py-2.5 … font-semibold text-sm"` (line ~5620). Product-card add: `"w-9 h-9 bg-[#f26522] hover:bg-[#d9561d] text-white rounded-lg"` (5860). PDP add-to-cart: `` `${d ? "bg-green-600 text-white" : R ? "bg-[#0a0a0a] text-white hover:bg-[#c85c2c]" : "bg-black/10 text-[#9e9890] cursor-not-allowed"}` `` (8286). Cart→checkout: `"w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 …"` (8925). Checkout Place Order: `"flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 …"` (9241). Vendor profile add: `"bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm"` (9723). Vendors CTA card: `"group block bg-[#c85c2c] border border-[#c85c2c] hover:bg-[#b54e24] …"` (10299). Bundles add: `"flex-1 bg-[#f26522] hover:bg-[#d9561d] text-white font-bold py-3 rounded-xl … shadow-lg shadow-[#f26522]/20"` (10920). So fills = {#f26522, #0a0a0a, #c85c2c, blue-600 #155dfc, green-600 #00a63e}; hovers = {#d9561d, #c85c2c, #b54e24, blue-700 #1447e6, green-700 #008236}. Contrast of white text on each fill: #f26522 3.15:1 FAIL, #c85c2c 4.18:1 FAIL, green-600 3.22:1 FAIL, blue-600 5.25:1 pass, #0a0a0a 19.8:1 pass.

**Impact.** Users cannot learn 'orange = buy'. Three of the five fills fail WCAG AA for their white label text. Also note the PDP confirmation state reuses green-600 — the same colour Checkout uses for its irreversible Place Order button — so 'Added to Cart' and 'Place Order' look identical.

**Fix.** Define exactly two button tokens — `--btn-primary` (one fill + one hover, both ≥4.5:1 against white text) and `--btn-secondary` — and apply them to every CTA in the funnel. Reserve green for confirmation *feedback* (icon + text), never for a button that spends money. Have Figma Make output a single Button component with variants instead of ad-hoc className strings.

**Magento.** Magento's `.action.primary` / `.action.secondary` are single global classes. Five fills means five overrides plus per-page scoping, and Knockout-rendered checkout buttons will need `!important` fights. Insist on the two-token model before theming; it collapses to `@button-primary__background` / `@button-primary__hover__background` in `_buttons.less`.

### `global-outline-color-neutralised` — A universal selector repaints every focus outline in the app to 10% black (1.26:1 on white, 1.04:1 on the navy header)

**Evidence.** make.css: `*{border-color:var(--border);outline-color:var(--ring)}@supports (color:color-mix(in lab,red,red)){*{outline-color:color-mix(in oklab,var(--ring)50%,transparent)}}` with `--ring:#0003` declared in :root. `#0003` = rgba(0,0,0,.2); the color-mix halves it again to rgba(0,0,0,.1).

**Impact.** This is an author-level rule on `*`, so it overrides the browser's own focus-ring colour on every button, link, card and form control in the build. Computed ratios: rgba(0,0,0,.1) vs #fff = 1.26:1, vs the #0f2144 header/footer = 1.04:1, vs the #f6f4f1 input surface = 1.26:1. WCAG 2.4.11 / 1.4.11 require 3:1. A keyboard user has effectively no focus indicator anywhere on the site — on the navy header the ring is mathematically invisible. Even the raw undiluted token `--ring:#0003` only reaches 1.61:1, so there is no correct value hiding behind the color-mix.

**Fix.** Delete `outline-color:var(--ring)` from the universal selector entirely, and redefine `--ring` as an opaque brand colour, not an alpha-on-black. Set `--ring: #0f2144` (15.89:1 on white) for light surfaces and add a `.on-dark` scope using `#f9884a` or `#ffffff`. Then add a single global rule: `:focus-visible{outline:3px solid var(--ring);outline-offset:2px}`. Do not let any token whose alpha channel is below 1 be used as a focus colour.

**Magento.** Magento/blank already ships a visible `:focus` outline on `.action`, `.field input`, and `#search`. If this compiled CSS is dropped into app/design/frontend/<Vendor>/<theme>/web/css, the `*{outline-color}` rule will silently strip focus visibility from core Luma/blank checkout and My Account components too, not just from MECommerce markup. Strip that line before any LESS import.

### `hero-scrim-gradient-is-physical-to-right` — Hero readability scrim is `bg-gradient-to-r` (hard-coded `to right`) while the hero text right-aligns in RTL — white text lands on the bright side of the photo

**Evidence.** Home hero (make.js ~243,524):
```
t("div", { className: `absolute inset-0 bg-gradient-to-r ${u.overlay}` }),
r("div", { className: "relative flex flex-col justify-center h-full px-8 md:px-12 py-10", children: [ …badge…, h1 "…max-w-sm…", p "…max-w-xs…", CTA ] })
```
Overlay values: `overlay: "from-[#0f2144]/85 via-[#0f2144]/50 to-transparent"`, `"from-[#0d3320]/90 via-[#0d3320]/50 to-transparent"`, `"from-[#2a1200]/90 via-[#2a1200]/50 to-transparent"`.
Compiled CSS: `.bg-gradient-to-r{--tw-gradient-position:to right in oklab;background-image:linear-gradient(var(--tw-gradient-stops))}` — `to right` is physical and does not respond to `direction`.
The same construct is reused on the 3 secondary hero tiles (`bg-gradient-to-r ${f.overlay} to-transparent`, ~246,640).

**Impact.** In RTL the flex column's `max-w-sm`/`max-w-xs` blocks align to the container's start edge, which is now the right — exactly where the gradient has faded to `to-transparent`. The headline, subtitle and CTA sit as white text over unmasked photography on the marketplace's single most prominent module, on 3 of 3 slides plus 3 promo tiles.

**Fix.** Replace `bg-gradient-to-r` with a direction-aware scrim: either `bg-linear-to-r rtl:bg-linear-to-l`, or an inline `background-image: linear-gradient(to inline-end, …)` / `linear-gradient(in oklab to right …)` guarded by `[dir=rtl]`. Simplest robust option: use a symmetric scrim (`to bottom` or a centred radial) so the hero is legible in both directions.

**Magento.** If the hero becomes a Page Builder banner, the overlay must be a store-view-scoped setting or a CSS class that RTLCSS can flip — Page Builder inline styles will not be transformed.

### `hover-only-add-to-cart-and-wishlist` — Add-to-cart and wishlist on the product grid are `opacity-0` until mouse hover, with no focus-within or touch fallback

**Evidence.** ProductCard default variant, make.js: wishlist `className: "absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-50"`; add-to-cart `className: "w-9 h-9 bg-[#f26522] hover:bg-[#d9561d] text-white rounded-lg flex items-center justify-center transition-colors shadow-sm opacity-0 group-hover:opacity-100"`. `focus-within` appears 6 times in the compiled CSS but 0 times in make.js, and there is no `group-focus-within:opacity-100` anywhere.

**Impact.** Three separate failures. (1) Touch: on phones and tablets there is no hover state, so quick-add and wishlist never become visible — the primary conversion control on the grid is unreachable for the majority of Gulf marketplace traffic. (2) Keyboard: `opacity-0` does not remove an element from the tab order, so a keyboard user tabs onto a fully invisible button — WCAG 2.4.7 and 2.4.11 Focus Not Obscured. (3) Low vision / zoom users lose the affordance entirely. Fails 2.1.1 and 1.4.13.

**Fix.** Replace `opacity-0 group-hover:opacity-100` with a permanently visible state on touch/small viewports and add focus support: `opacity-100 md:opacity-0 md:group-hover:opacity-100 md:group-focus-within:opacity-100 focus-visible:opacity-100`. The safest option for a marketplace is to make both buttons always visible.

### `inline-fontfamily-blocks-arabic` — 45 inline `fontFamily` style objects make the Arabic font swap impossible without !important

**Evidence.** `style: { fontFamily: "'Playfair Display', Georgia, serif" }` appears 40 times and `style: { fontFamily: "'DM Sans', system-ui, sans-serif" }` 5 times (page roots at 8103, 10540, and the layout root 5601). Zero uses of a `font-sans`/`font-serif`/`font-display` utility (`font-mono` ×1 is the only font utility in the bundle). make.css independently sets the same faces at element level: `h1{…font-family:Playfair Display,Georgia,serif;font-weight:700…}`, `h2{…}`, `h3{…}`, `body{font-family:DM Sans,system-ui,sans-serif}` — so headings carry the face twice, once from CSS and once inline.

**Impact.** Neither Playfair Display nor DM Sans has Arabic glyphs. Every Arabic heading will fall through to `Georgia` → browser default, giving a different typeface on every OS. The normal fix — an `[dir=rtl] { font-family: <Arabic face> }` rule — cannot win against 45 inline `style` attributes, because inline styles beat any stylesheet declaration short of `!important`.

**Fix.** Instruct Figma Make to delete every inline `fontFamily` and express the two faces as utilities (`font-display` for Playfair, default sans for DM Sans) defined once in the theme layer. Then add an Arabic display/body pair (e.g. Noto Kufi Arabic / IBM Plex Sans Arabic) bound to the same utilities under `[dir=rtl]`.

**Magento.** Magento loads fonts through the theme's `_typography.less` / `@font-face` declarations and switches per store view. Inline React `style` props do not survive into `.phtml` cleanly — they become inline `style=""` attributes that no theme LESS can override. This must be fixed in the design source, not patched in Magento.

### `lang-toggle-is-direction-only-no-translation-layer` — There is no i18n layer at all — the "Arabic" toggle only flips html[dir] and html[lang]; 100% of copy stays English

**Evidence.** Only handler in the bundle (make.js ~157,300, component `oc` = Layout):
```
g = () => {
  const p = !i;
  n(p), document.documentElement.dir = p ? "rtl" : "ltr", document.documentElement.lang = p ? "ar" : "en";
};
```
Exhaustive Arabic-script scan of make.js returns exactly 4 runs, all inside the toggle's own label: `عربي`, and `التحويل إلى العربية`. Zero `i18n`, `useTranslation`, `IntlProvider`, message catalogue or key lookup anywhere in 460 KB. Every other string is a hard-coded English literal (`children: "Login"`, `children: "Sell on MECommerce"`, `children: "Proceed to Checkout"`, …).

**Impact.** Clicking "عربي" produces an Arabic-direction page containing only English words — the worst of both worlds. Nothing in the design tells the Magento build what the Arabic string set is, so the entire ar store view has to be authored from scratch with zero reference, and every screen has to be re-QA'd for length. It also means none of the layout decisions in the design were ever tested against real Arabic text.

**Fix.** Extract every user-visible string into a keyed dictionary (`en.json` / `ar.json`) and render through a `t('key')` helper. Author real Arabic for at least Home, Category, Product, Cart, Checkout and the header/footer chrome, and set the design's default preview to Arabic so layout is validated against real text (Arabic runs ~25–40% longer than English in nav labels and ~10–20% shorter in body copy).

**Magento.** Map the dictionary 1:1 onto `i18n/ar_SA.csv` in the theme plus per-store-view CMS blocks. The repo already has `ar_SA.csv` and `ar_SA_vendor.csv` at project root — reconcile keys with those rather than inventing a second vocabulary. Vnecoms vendor-facing strings need their own csv in the Vnecoms modules.

### `letter-spacing-and-uppercase-break-arabic` — 55 letter-spacing declarations and 42 uppercase transforms — both are destructive or meaningless in Arabic

**Evidence.** Tokens: `--tracking-widest:.1em`, `--tracking-wider:.05em`, `--tracking-wide:.025em`, `--tracking-tight:-.025em`. Usage in make.js: `tracking-widest` ×42, `tracking-wider` ×4, `tracking-wide` ×4, `tracking-tight` ×2; inline `letterSpacing: "-0.02em"` on 3 h1s; plus the base rule `h1{letter-spacing:-.01em}`. `uppercase` appears 42× and 41 of those are paired with `tracking-widest`, e.g. `t("p", { className: "text-[10px] tracking-widest uppercase text-[#c85c2c] mb-2", children: "More Like This" })`, `t("h4", { className: "text-[10px] tracking-widest uppercase text-white/35 mb-4", children: p.title })`, `t("p", { className: "text-[9px] tracking-widest uppercase text-[#9e9890] mb-2", children: "Today's Overview" })`.

**Impact.** Arabic is a cursive connected script. Positive letter-spacing pulls the kashida joins apart and renders words as a row of disconnected letterforms — this is a correctness failure, not a taste issue. Negative tracking (-0.01em/-0.02em on h1s) overlaps them. Separately, `text-transform: uppercase` is a complete no-op in Arabic because Arabic has no case, so the design's single most-used labelling device — 9-10px + uppercase + 0.1em tracking, used 41 times as the eyebrow/kicker across Home, PDP, Vendors, Features, Platform and the footer — collapses in Arabic into an undifferentiated 9-10px run of text with nothing distinguishing it from body copy.

**Fix.** Add `:lang(ar), [dir="rtl"] { letter-spacing: 0 !important; text-transform: none; }` as a hard guard. Then design an Arabic-native equivalent for the eyebrow label — weight 600 + accent colour + a 24px rule above, at minimum 14px — and make it a component so both locales use the same slot. Do not simply translate the uppercase labels; the visual device has to change.

**Magento.** Magento's RTL handling is layout-only; nothing in `Magento_Theme` neutralises letter-spacing. Put the guard in `_typography.less` before any component styles so third-party modules (Vnecoms vendor blocks, review widgets) inherit it.

### `logo-mirrors-to-commerceme` — Logo is two flex siblings, so RTL reverses it to "CommerceME"

**Evidence.** Header (make.js ~159,780) and footer (~166,900) both use the identical construct:
```
r(C, { to: "/", className: "flex-shrink-0 flex items-baseline gap-0.5 pr-2 border-r border-white/10 mr-1", children: [
  t("span", { className: "text-[#f26522] text-xl font-extrabold tracking-tight", style: { fontFamily: "'Playfair Display', Georgia, serif" }, children: "ME" }),
  t("span", { className: "text-white text-xl font-bold tracking-tight", style: { fontFamily: "'Playfair Display', Georgia, serif" }, children: "Commerce" })
] })
```
`display:flex` with default `flex-direction:row` reverses its main axis when `direction:rtl` is inherited, so the `"ME"` span paints to the right of the `"Commerce"` span.

**Impact.** The brand name is wrong on every page of the Arabic store view, in both header and footer. A brand mark is never mirrored, regardless of direction.

**Fix.** Put the wordmark in a single element with `dir="ltr"` and colour the "ME" with an inner `<span>` that is not a flex item — e.g. `<span dir="ltr" style="font-family:'Playfair Display'"><span style="color:#f26522">ME</span>Commerce</span>`. Do not use `display:flex` for the wordmark. Also change the adjacent divider `pr-2 border-r … mr-1` to `pe-2 border-e … me-1` so the rule sits on the trailing edge in both directions.

**Magento.** Ship the logo as a single SVG/image asset in the theme (`Magento_Theme::html/header/logo.phtml`) rather than composed text, so it can never mirror. If a separate Arabic lockup is wanted, override the logo per store view.

### `missing-commerce-semantic-tokens` — Every commerce-semantic colour is missing from the token set; each is expressed by 3–5 competing literals

**Evidence.** No token exists for price, was-price, discount, rating, stock, verified, delivery, bundle, success, warning or info. What is used instead:
• CURRENT PRICE — 4 treatments: `text-sm font-bold text-[#0f2144]` (5770), `text-lg font-extrabold text-[#0f2144]` (5855), `text-4xl text-[#0a0a0a]` + `style:{fontFamily:"'Playfair Display'…",fontWeight:700}` (PDP 8171), `text-sm font-extrabold text-white` (Home dark card 7656), and bare `"text-lg font-bold"` inheriting `--foreground` on Cart 8848 / CustomerProfile 9714.
• WAS PRICE — 4: `text-gray-400 line-through` ×9 (5776,5802,5859,5904,5972,8554,10858,10890…), `text-gray-500 line-through` ×2 (8854, 9719), `text-white/30 line-through` (7660), `text-lg text-[#9e9890] line-through` (8180).
• DISCOUNT BADGE — 5: `"absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold"` ×4 (5787,5828,7637,8532), `"bg-[#f26522] text-white text-[9px] font-bold"` ×3 (5892,5933,10832 '-X% Bundle'), `"text-xs tracking-widest uppercase text-white bg-[#c85c2c] px-2 py-1"` (PDP 'Save X%' 8184), `"bg-green-100 text-green-700 text-sm font-bold px-3 py-1 rounded-full"` (Bundles 10894), `"text-xs font-bold text-green-600"` 'Save $X' (5908, 5977).
• RATING STAR — 3 fills / 3 empties: `fill-amber-400 text-amber-400` ×9, `fill-yellow-400 text-yellow-400` ×4 (9345, 9474, 9485, 9502), `fill-[#c85c2c] text-[#c85c2c]` (8156, 10164); empty star `text-gray-200` / `text-white/15` (7647) / `text-black/15` (8156). Plus raw glyphs `" ★  ·  "` (8314), `" ★ • "` (8746), `"⭐ Top Vendors This Month"` (7935), `"4.8 ★"` (10649).
• STOCK — 4: `"bg-gray-800 text-white text-xs font-bold px-3 py-1.5 rounded-full"` overlay (5832), `"text-xs text-red-500 font-medium"` (8558), `` `${e.stock > 0 ? "text-green-600" : "text-red-500"}` `` (10681), PDP disabled swatch `"border-black/8 text-[#c9…"` (8243).
• VERIFIED VENDOR — 3: `text-[#f26522]` (7734, 7957, 8008 '✓ Verified'), `text-[#c85c2c]` (8310, 10152, 10252), `text-blue-600` (9340).
• FREE DELIVERY — 4: `"text-green-600"` 'FREE' (8907, 9285), `{icon:Ht,text:"Free shipping on orders over $50",color:"text-[#c85c2c]"}` (8297), `"text-xs text-gray-500 … bg-gray-50"` chip 'Free delivery over AED 150' (10942), `t(Ht,{className:"w-3 h-3 text-[#f26522]"})` in the promo bar (5604).
• BUNDLE BADGE — 4: `bg-[#f26522] text-white`, `bg-green-100 text-green-700`, `"bg-amber-400 text-[#1a4731] text-[9px] font-bold tracking-widest uppercase"` (7819), `"bg-amber-400 text-amber-900 text-[9px] font-extrabold uppercase"` (7952).
• DESTRUCTIVE — the declared `--destructive:#c0392b` occurs exactly ONCE in the build (its own declaration) and 0 times in make.js; real errors use `red-500` #fb2c36 (Δrgb=76 from the token).

**Impact.** Price, savings, stock and trust signals — the pixels that drive conversion — are inconsistent per page and per card variant. A shopper sees the same 'was' price in four greys and the same discount in five badge colours. Nothing can be A/B tested or centrally adjusted, and merchandising teams have no lever.

**Fix.** Have Figma Make add and apply a commerce token block: `--price`, `--price-old`, `--price-special`, `--discount-badge-bg/-fg`, `--rating-star`, `--rating-star-empty`, `--in-stock`, `--out-of-stock`, `--verified`, `--free-delivery`, `--bundle-badge-bg/-fg`, `--success`, `--warning`, `--info`, plus fix `--destructive` to the value actually used. One value each; every card variant, PDP, cart, checkout and bundle page must consume the token, not a literal.

**Magento.** These map 1:1 onto Magento price/stock template hooks: `.price-box .price` → `--price`; `.old-price .price` → `--price-old`; `.special-price` → `--price-special`; `.stock.available` / `.stock.unavailable` → in/out-of-stock; `.rating-result:before` → `--rating-star` (Luma draws stars with `content:'★★★★★'`, so the token must be a text colour, which also cleanly replaces the raw ★ glyphs); Vnecoms vendor 'verified' badge → `--verified`. Without these tokens the Magento side has to invent them anyway — better to agree the names now so LESS variables and the Figma file match.

### `missing-interaction-states` — Loading, skeleton, error and most disabled/empty states do not exist anywhere in the bundle

**Evidence.** Grep across make.js: `skeleton` → 0, `animate-pulse` → 1 (a decorative ring: `"absolute -inset-6 border border-[#f26522]/10 rounded-full -z-10 animate-pulse"`, 8075), `animate-spin` → 1 (only the AI-recommendation refresh button, 7596), `aria-live` → 0, `role:` → 0, `sr-only` → 0 (the class is compiled into make.css but never used), `fixed` → 0 (so no modal, no toast, no mobile sticky buy bar).
DISABLED: `disabled` appears 5 times, all on the Product page or the AI widget — `disabled: !R` on Add to Cart (8285), `disabled: !j.inStock` on a variant option (8225), `disabled: !0` on a placeholder `<option>` (8216), `disabled: c` on refresh (7593). Nothing else in the app has a disabled state — including "Proceed to Checkout", "Place Order", "Continue to Payment" and all quantity steppers (the minus button at 8262/8871/10920 stays enabled at quantity 1 and merely no-ops via `Math.max(1, …)`).
ERROR: no field-level error component exists. The only validation surface is two static hints on the PDP — `"Please select all options before adding to cart"` and `"One or more selected options are out of stock"` — plus `"border-red-300 text-red-500 focus:border-red-400"` on one `<select>` (8210). The entire checkout form (12 inputs) has no error state, no required marker, no inline message slot.
OUT OF STOCK: handled in exactly 2 of 12 product renderings (`!e.inStock &&` overlay at 5830; inline `"text-xs text-red-500 font-medium"` at 8558).
EMPTY: 5 exist, in 5 different designs — Cart (`"w-24 h-24 text-gray-300"` lucide bag, 8810), Category (`"text-4xl mb-3", children: "🔍"` + a working "Clear all filters" action, 8506), Vendors (`"🔍"`, no action, 10226), Bundles (`"📦"`, no action, 10998), Search (`"w-16 h-16 text-gray-300"` lucide, no action, 8714). MISSING entirely: empty wishlist (9692 maps `n` with no guard), empty order history, empty saved addresses, and VendorProfile with zero products (`children: l.map((s) => t(tt, …))` at 9422 renders a bare empty grid).

**Impact.** Magento is a server-rendered app on real network latency — category filtering, search, add-to-cart and checkout step transitions all have visible waits. With no skeleton or spinner spec the implementation will invent them, inconsistently. With no error spec, checkout validation failures have no design at all, which is the single highest-risk screen in the funnel. Users on a slow Gulf mobile connection see a blank region and assume the page is broken.

**Fix.** Ask Figma Make to add, as reusable components: (1) ProductCardSkeleton + a grid skeleton at each breakpoint; (2) a Spinner and a button `loading` state; (3) FormField error state — red border, message slot, `aria-describedby`; (4) a page-level error/retry block; (5) disabled states for every primary CTA and for the stepper minus at qty 1; (6) one EmptyState component with `icon`, `title`, `body`, `action` props, used for all 9 empty cases (5 existing + 4 missing).

**Magento.** Magento core already emits `.loading-mask`/`.loader` overlays for checkout and add-to-cart, and `mage/validation` renders `div.mage-error` under fields. The theme must style those specific hooks — so the design needs to specify them, otherwise the build ships core Luma loaders inside a bespoke storefront. Same for `Magento_Checkout` error messaging and `Magento_Catalog` stock status.

### `muted-text-two-ramps-both-fail-aa` — Two competing muted-text ramps are in use (#9e9890 and gray-400 #99a1af) — both fail WCAG AA badly; the compliant declared token #6b7280 is never applied

**Evidence.** Declared token: `--muted-foreground:#6b7280` — appears exactly ONCE in the whole build (its own declaration in make.css) and 0 times in make.js. What is actually rendered:
• `.text-\[\#9e9890\]{color:#9e9890}` — 35 uses in make.js (Product 9, Vendors 8, Platform 15, Features 3). Contrast on #fff = **2.86:1**; on its own warm surface #f6f4f1 = **2.60:1**. Size distribution of those 35: `text-[9px]` ×13, `text-[10px]` ×4, `text-[8px]` ×2, `text-xs` ×5, `text-[11px]` ×2, `text-sm` ×3, `text-lg` ×1 — i.e. 19 of 35 are at 8–10px. Example: `t("p",{className:"text-[9px] tracking-widest uppercase text-[#9e9890] mb-2",children:"Recent Orders"})` (10653).
• `text-gray-400` — 36 uses, resolves from `--color-gray-400:oklch(70.7% .022 261.325)` = **#99a1af**, contrast on white **2.60:1**. Used for every strikethrough was-price and most secondary labels.
• `text-[#6b6560]` — 18 uses (5.74:1, passes) — a THIRD muted grey.
• `text-gray-500` #6a7282 — 19 uses (4.84:1, marginal pass).
Δrgb between the ramps: #9e9890 vs #6b7280 = 66; #6b7280 vs #6b6560 = 35; #9e9890 vs #99a1af = 15 (visually the same grey, two different systems).

**Impact.** Roughly 71 pieces of secondary text — vendor names, review counts, 'was' prices, delivery notes, section eyebrows — render at 2.6–2.9:1, well under the 4.5:1 AA floor, and most of them at 8–10px. This is the single largest accessibility exposure in the design and it also affects Arabic, where DM Sans has no glyphs and the fallback font will render even lighter-weight strokes at these sizes. It also means the design has four greys pretending to be one.

**Fix.** Collapse to one `--muted-foreground` at ≥4.5:1 on both #fff and the chosen muted surface (e.g. #5f6672 = 5.6:1, or keep #6b7280 at 4.83:1 and raise the minimum font size to 12px). Replace all 35 `text-[#9e9890]`, all 36 `text-gray-400` and all 18 `text-[#6b6560]` with it. Ban 8–10px body text outright: minimum 12px for anything a customer must read.

**Magento.** Maps to `@text__color__muted` / `@color-gray*` in `_theme.less`. Important for the Arabic store view: set the RTL minimum font-size a step higher than LTR in `_rtl.less`, since the Arabic fallback face (no Arabic in DM Sans) renders lighter and 9px Arabic at 2.86:1 is unreadable.

### `nineteen-buttons-with-no-accessible-name` — 19 icon-only buttons have zero accessible name — the string "aria-label" does not appear anywhere in the bundle

**Evidence.** `grep -c 'aria-' make.js` = 2, and both are `"aria-current"` inside react-router's unused NavLink internals. `role:`, `tabIndex`, `aria-hidden`, `aria-expanded` all = 0. Nameless buttons include: mobile menu toggle `className: "md:hidden text-white", onClick: () => a((p) => !p), children: e ? t(nc,{className:"w-6 h-6"}) : t(Po,{className:"w-6 h-6"})`; mobile search submit `{ type: "submit", className: "bg-[#f26522] text-white px-4", children: t(Ue,{className:"w-4 h-4"}) }`; carousel prev/next `"absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 bg-black/25 ..."`; cart quantity steppers `{ onClick: () => i(p.id, -1), className: "p-2 hover:bg-gray-50", children: t(jo,{className:"w-4 h-4"}) }`; cart remove `{ onClick: () => n(p.id), className: "text-red-600 hover:text-red-700 p-2", children: t(Zo,{className:"w-5 h-5"}) }`; PDP gallery thumbnails `{ onClick: () => s(w), className: "aspect-square bg-[#f6f4f1] ...", children: t("img",{src:E, alt:"", className:"w-full h-full object-cover"}) }`; PDP wishlist/share `"border border-black/15 p-3.5 hover:border-[#0a0a0a] transition-colors text-[#0a0a0a]"`; grid/list toggles `{ onClick: () => n("grid"), className: "p-2 ..." }`.

**Impact.** A screen reader announces each of these as bare "button". On the Cart page a user hears "button, button, 3, button, button" for every line item and cannot tell remove from decrease from increase. The PDP thumbnail buttons wrap `<img alt="">`, so they have literally no text node at all. Fails WCAG 4.1.2 Name, Role, Value and 2.4.4.

**Fix.** Add `aria-label` to every icon-only control: "Open menu"/"Close menu" (plus `aria-expanded` and `aria-controls` on the toggle), "Search", "Previous slide"/"Next slide", "Decrease quantity of {product}", "Increase quantity of {product}", "Remove {product} from cart", "Add {product} to wishlist", "Share", "Grid view"/"List view". For gallery thumbnails, replace `alt=""` with `alt={`View image ${w+1} of ${p.length}`}`.

**Magento.** Vnecoms vendor-facing grids reuse the same icon-button pattern. Bake the aria-label into the .phtml partials (a single `hub_icon_button.phtml` taking a `label` argument) rather than into the theme LESS, so it survives module upgrades.

### `no-order-success-page` — 'Place Order' is a dead button and no order-success page exists

**Evidence.** make.js:9243 `r("button", { type: "button", className: "flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold ...", children: [ t(Ea, ...), "Place Order" ] })` — no `onClick`. The router has no `checkout/success` path (make.js:11046-11061).

**Impact.** The single highest-value page in the funnel is undesigned: no order number, no per-vendor order breakdown, no 'create an account' upsell, no continue-shopping CTA, and nowhere to fire the purchase analytics/conversion pixel.

**Fix.** Design `checkout/onepage/success`: order number(s), one card per vendor sub-order with its own items/shipping/ETA, payment summary, print/track links, guest 'create account' prompt, and a recommendations strip.

**Magento.** With Vnecoms one checkout produces N vendor orders, so the success page must show a parent reference plus N order increment IDs — a single 'Order #' line will be wrong.

### `playfair-on-every-heading` — An unscoped base rule puts Playfair Display on 100% of h1/h2/h3 — including 12px and 14px product-card titles

**Evidence.** make.css @layer base: `html{font-size:var(--font-size)}h1{font-size:var(--text-2xl);letter-spacing:-.01em;font-family:Playfair Display,Georgia,serif;font-weight:700;line-height:1.15}h2{font-size:var(--text-xl);font-family:Playfair Display,Georgia,serif;font-weight:700;line-height:1.2}h3{font-size:var(--text-lg);font-family:Playfair Display,Georgia,serif;font-weight:600;line-height:1.3}h4{font-size:var(--text-base);font-weight:var(--font-weight-medium);line-height:1.5}body{font-family:DM Sans,system-ui,sans-serif}`. There is no shadcn `:where(:not([class*=text-]))` guard (0 matches). make.html declares `@layer figreset,figoverridable,reset,theme,base,figutils,components,utilities;` so a utility overrides font-size but NEVER font-family. Product-card title: `"h3", { className: "text-xs font-medium text-gray-800 line-clamp-2 leading-snug group-hover:text-[#f26522] transition-colors", children: e.name }`. Bundle contains 18 h1, 36 app h2, 43 app h3; only 9/18, 16/36 and 4/43 declare `fontFamily: "'Playfair Display', Georgia, serif"` inline — the other 68 inherit the serif silently.

**Impact.** Every product title on every listing renders in a high-contrast Didone display serif at 12px or 14px. Playfair's hairline strokes disappear at that size and its narrow, high-contrast forms are the worst possible face for the scanning task a PLP exists to serve. Filter headings (`"h3", { className: "text-sm font-bold text-[#0f2144] mb-3", children: "Price Range" }`), checkout section titles (`"h3", { className: "font-semibold mb-2", children: "Shipping Address" }`) and 18 unsized h3s all become 18px Playfair too. No developer reading the JSX would predict this — the class strings say `font-medium text-gray-800`, nothing says serif.

**Fix.** Delete `font-family` from the `h1,h2,h3` base rule (leave size/weight/leading or move those to classes too). Introduce a single opt-in `.font-display` utility and apply Playfair ONLY to: hero headline, page-level h1 on marketing pages (Vendors/Features/Platform/About), and section headline h2s. Explicitly force `font-family: 'DM Sans'` on product titles, vendor-card names, filter headings, checkout step titles, form labels and every heading below 20px.

**Magento.** Do not port `h1,h2,h3{font-family:...}` into `web/css/source/_typography.less`. Magento renders `.page-title` as h1, checkout step titles as h2/h3, and Vnecoms vendor dashboard/storefront blocks emit their own h2/h3 — a blanket element rule silently reskins all of them plus any admin-authored CMS block. Bind the serif to a class (`.display-title`) and add it in the templates you control.

### `playfair-on-pdp-price` — The PDP price is set in Playfair Display — the only serif price in the entire funnel

**Evidence.** PDP: `/* @__PURE__ */ r("span", { className: "text-4xl text-[#0a0a0a]", style: { fontFamily: "'Playfair Display', Georgia, serif", fontWeight: 700 }, children: [ "$", f ] })`. Marketing stat numerals likewise: `t("div", { className: "text-2xl text-[#0a0a0a] mb-1", style: { fontFamily: "'Playfair Display', Georgia, serif", fontWeight: 700 }, children: u.val })` and `t("p", { className: "text-2xl font-bold text-[#f26522]", style: { fontFamily: "'Playfair Display', Georgia, serif" }, children: n.val })`. Every other price in the app is DM Sans, e.g. `r("span", { className: "text-lg font-extrabold text-[#0f2144]", children: [ "$", e.price ] })`.

**Impact.** A shopper moving PLP → PDP sees the same datum change typeface. Playfair is a Didone: its digit strokes go from thick stem to hairline, so prices lose stroke on non-retina Android and in print/screenshot. It also has no tabular figures applied, so a price with a '1' and a price with a '8' occupy different widths in the same column.

**Fix.** Remove `fontFamily: "'Playfair Display', Georgia, serif"` from every element that renders a number — PDP price, was-price, all `{ val, label }` stat blocks, bundle savings. Build one `<Price>` component in DM Sans 600 with `font-variant-numeric: tabular-nums` and reuse it everywhere.

**Magento.** `.price-box .price`, `.old-price .price`, `.special-price`, `.price-including-tax` must all resolve to the same family/feature settings in `_typography.less`; Vnecoms vendor-product blocks reuse the core price template, so getting this right once covers vendor pages.

### `product-card-12-renderings` — 12 distinct product-card renderings; only one is a component, and one of its variants is dead code

**Evidence.** Single component: `function tt({ product: e, variant: a = "default" })` (make.js:5764) branches `a === "wide" ? … : a === "compact" ? … : …`. Call sites: 7 total, of which only ONE passes a variant — `t(tt, { product: S, variant: "compact" }, S.id + N)` (7776). `variant: "wide"` is never passed anywhere in the bundle (grep `variant: "` returns exactly 1 hit). Seven further hand-rolled product renderings exist with no shared component: AI card `"group bg-white/6 border border-white/8 rounded-2xl overflow-hidden…"` (7625); best-seller rank card `"group bg-white border border-gray-100 rounded-xl overflow-hidden…"` (7859); Category list row `"group flex gap-4 bg-white rounded-xl border border-gray-100 p-4…"` (8527); wishlist card `"bg-white border rounded-lg overflow-hidden group"` (9698); cart line item `"p-6 flex gap-4"` (8837); order line item `"flex gap-4"` (9658); checkout review row `"flex gap-3 pb-3 border-b last:border-0"` (9206). Plus 2 decorative skeleton mockups (8065, 10702).

Attribute diff of the three most-used:
• image — default/compact: `aspect-square`; wide: `w-16 h-16`; list row: `w-28 h-28`; cart: `w-24 h-24`; order: `w-20 h-20`; checkout: `w-16 h-16`
• discount badge — default `"absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm"`; compact `"…text-[9px]…px-1.5 py-0.5 rounded-full"` (no shadow); AI card `"absolute top-2.5 left-2.5 bg-red-500 … text-[10px] px-2 py-0.5"`; list row `"absolute top-1.5 left-1.5 … text-[9px] px-1.5"`; wishlist card: none at all
• vendor label — default `"text-[10px] text-[#f26522] font-semibold mb-1 truncate"`; compact `text-[9px]`; AI card `"text-[10px] text-white/35"` (no accent); best-seller: absent; wishlist `"text-sm text-gray-600"`
• rating — default 5×`w-3 h-3` + `(reviews)`; compact 5×`w-2.5 h-2.5`, no review count; best-seller 5×`w-2.5 h-2.5`, no count; wishlist/cart/order: no rating at all
• price — default `"text-lg font-extrabold text-[#0f2144]"`; compact `"text-sm font-bold text-[#0f2144]"`; best-seller `"text-sm font-extrabold text-[#f26522]"` (orange, no strike-through price); wishlist `"text-lg font-bold"` with the strike price stacked on its own line
• add-to-cart — default `"w-9 h-9 bg-[#f26522] … rounded-lg … opacity-0 group-hover:opacity-100"`; compact `"w-6 h-6 bg-[#f26522] … rounded-md"` always visible; AI card `"w-7 h-7 rounded-full"` with per-item `style: { backgroundColor: R + "33" }`; best-seller/list-row: none; wishlist: a `<Link>` styled `"bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm"` labelled "Add to Cart"
• out-of-stock — default only: `"absolute inset-0 bg-white/60"` + `"bg-gray-800 text-white text-xs font-bold px-3 py-1.5 rounded-full"`; list row uses inline `"text-xs text-red-500 font-medium"`; every other rendering ignores `inStock` entirely
• hover — `hover:shadow-lg hover:border-[#f26522]/20` (default) vs `hover:shadow-md hover:border-[#f26522]/25` (compact) vs `hover:bg-orange-50/40` (wide) vs `hover:border-white/20 hover:bg-white/10` (AI)

**Impact.** A shopper sees the same product presented six different ways within one scroll of the homepage: with a review count, without one; with an orange price, with a navy price; with an always-visible cart button, with a hover-only one, with none. Out-of-stock is invisible in 10 of 12 renderings, so a customer can click through to a PDP that is unbuyable. There is no single source of truth to change when the card spec changes.

**Fix.** Collapse all 12 into ONE ProductCard with declared props: `layout: 'grid' | 'row' | 'line-item'`, `density: 'sm' | 'md'`, `theme: 'light' | 'dark'`, `showRating`, `showReviewCount`, `showVendor`, `showDescription`, `showAddToCart: 'always' | 'hover' | 'none'`, `showWishlist`, `badge?: {kind, label}`, `rank?: number`. Delete the `wide` variant (0 call sites). Fix the price token to one value (`text-[#0f2144]`) and make the out-of-stock treatment unconditional inside the component, not per-call-site.

**Magento.** In Magento this is one `.phtml` — `Magento_Catalog::product/list/item.phtml` overridden once in the theme, with a ViewModel exposing the flags. Vnecoms vendor name comes from `Vnecoms\Vendors` seller data; render it through a single block so it can be hidden per store view. Do not let the 12 variants become 12 templates — the current Figma output would produce ~12 template overrides plus 12 sets of LESS, which is unmaintainable across 2 locales.

### `range-slider-has-no-name` — The category price filter is an `<input type="range">` with no label, no aria-label and no aria-valuetext

**Evidence.** make.js Category page: `t("input", { type: "range", min: "0", max: "2000", value: c, onChange: (w) => o(Number(w.target.value)), className: "w-full accent-[#f26522]" })`. The words "Price Range" live in a sibling `t("h3", { className: "text-sm font-bold text-[#0f2144] mb-3", children: "Price Range" })`, and the current value lives in a separate `r("span", { className: "font-semibold text-[#f26522]", children: ["Up to $", c] })`.

**Impact.** A screen reader announces "slider, 1000" with no name and no unit — the user does not know it is price, or what currency, or that 1000 means "up to 1000". Fails 4.1.2 and 1.3.1. The visible value readout is also never associated, so it is not announced on drag.

**Fix.** Add `aria-label="Maximum price"` (or `aria-labelledby` pointing at the h3's id) plus `aria-valuetext={`Up to ${c} AED`}` so the announced value carries the unit. Wire the readout span with `aria-describedby`.

### `rtl-physical-properties-only` — The app flips `dir` to RTL but uses zero logical properties — every directional utility stays physical

**Evidence.** The toggle exists: `n(p), document.documentElement.dir = p ? "rtl" : "ltr", document.documentElement.lang = p ? "ar" : "en"` (make.js:5599). But the bundle contains **0** occurrences of `ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`, and 0 `[dir=rtl]` rules in make.css. Instead: `ml-*` ×17, `mr-*` ×2, `pl-*` ×2, `pr-*` ×6, `left-*` ×15, `right-*` ×17, `border-r` ×6, `border-l` ×1.
Concrete breakages:
• Site search — icon `"absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"` (8596) over input `"w-full pl-12 pr-4 …"` (8604). In RTL the text starts on the right and runs into nothing on the left; the magnifier sits in dead space. Identical pattern on Vendors search: `"w-4 h-4 absolute left-3 …"` (10176) + `"w-full pl-9 pr-4 …"` (10184).
• Every `<select>` chevron — `"w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 …"` (8237) with `"… px-4 py-3 pr-10 …"`; same at 8484/8480 and 10198/10194. In RTL the chevron overlaps the option text.
• Cart count badge `"absolute -top-1.5 -right-1.5 bg-[#f26522] … w-4 h-4 …"` (5666) stays on the right of the cart glyph.
• Discount badges `"absolute top-2 left-2 …"` (5786, 5827, 7636, 8531, 10830) stay left; wishlist hearts `"absolute top-2 right-2 …"` (5836) and `"absolute top-3 right-3 …"` (9708) stay right — they swap roles in RTL.
• Verified check `"… absolute -bottom-0.5 -right-0.5"` (7734, 7957) detaches from the avatar corner.
• Logo divider `"flex-shrink-0 flex items-baseline gap-0.5 pr-2 border-r border-white/10 mr-1"` (5625) puts the rule on the wrong side of the wordmark.
• Quantity stepper: minus button `"… border-r border-black/15 …"` (8265) and plus `"… border-l border-black/15 …"` (8274) — in RTL the buttons swap visual order but the borders do not, giving a doubled rule on one side and none on the other.
• Nav `"… flex-shrink-0 border-b-2 border-transparent ml-auto"` on the Bundle Deals link (5704) loses its right-edge alignment.
• Carousel `"absolute left-3 …"` prev / `"absolute right-3 …"` next (7485, 7493) with unflipped chevron glyphs.

**Impact.** The Arabic store view — one of the two live locales — renders with overlapping search icons, misplaced badges and broken input affordances on every page. This is not a polish issue; the RTL storefront is unusable as designed.

**Fix.** Instruct Figma Make to replace every physical utility with its logical equivalent: `ml-*`→`ms-*`, `mr-*`→`me-*`, `pl-*`→`ps-*`, `pr-*`→`pe-*`, `left-*`→`start-*`, `right-*`→`end-*`, `border-r`→`border-e`, `border-l`→`border-s`, `text-left`→`text-start`. For the chevron/arrow glyphs add `rtl:rotate-180`. Then produce and review an RTL screenshot of Home, Category, Product, Cart and Checkout before handover.

**Magento.** Magento 2.4.8 has no automatic RTL flip either — `Magento/luma` ships a separate `_rtl.less`. Whatever the design hands over will be transcribed literally. Logical properties are supported in every browser Magento 2.4.8 targets, so the theme should be built on them from the start; retrofitting an `[dir=rtl]` override sheet for 60+ physical utilities across 12 pages is roughly a week of avoidable work.

### `rtl-scrambles-english-copy-115-strings` — With dir=rtl and English copy, 115 user-visible strings render in scrambled visual order (bidi reordering of Latin text in an RTL paragraph)

**Evidence.** Ran every `children:`/`placeholder:`/`label:`/`sub:`/`desc:` string literal in make.js through a UAX#9 reordering with base direction R. 115 come out visually different. Representative confirmed cases:
`"Search products, brands, vendors across all categories…"` → `…Search products, brands, vendors across all categories`
`"Sofas, beds, and lighting from the Gulf's top furniture vendors."` → `.Sofas, beds, and lighting from the Gulf's top furniture vendors`
`"Tax (10%)"` → `(Tax (10%`
`"Shopping Cart (" , n, " items)"` → `(Shopping Cart (3 items`
`"(", a.reviews, " reviews)"` → `(reviews 128)`
`"2,400+ items"` → `items +2,400`  (×8 category tiles)
`"256 GB"` / `"1 TB"` / `"9 kg"` / `"1.5 Ton"` → `GB 256` / `TB 1` / `kg 9` / `Ton 1.5`  (all product variant option labels)
`"🎉 You qualify for free shipping!"` → `!You qualify for free shipping 🎉`
`"#1 Vendor"` → `Vendor #1`; `"✓ Verified"` → `Verified ✓`; `"4.8 ★"` → `★ 4.8`
`"© 2026 MECommerce. All rights reserved. Powered by Magento 2."` → `.MECommerce. All rights reserved. Powered by Magento 2 2026 ©`

**Impact.** This is the root cause of the two artefacts already seen by eye (leading ellipsis in the search placeholder, leading full stop in the hero subtitle) — but it is not two bugs, it is 115. Every trailing `.`/`…`/`!`/`?`/`:`/`+` jumps to the opposite end of the line, and every number-plus-unit pair transposes. The variant selector on Product becomes unusable: a shopper picking storage sees `GB 256`, `GB 512`, `TB 1`.

**Fix.** Two-part. (a) Do the translation work in finding `lang-toggle-is-direction-only-no-translation-layer` — correctly translated Arabic does not exhibit this. (b) Until then, any string that is intentionally left in English must carry `dir="ltr"` on its own element, and the design must stop building strings by concatenating JSX children (`children: ["(", a.reviews, " reviews)"]`) — use a single interpolated, translatable string per label so the translator controls token order.

**Magento.** In Magento the same trap exists in `__('%1 items')`-style phrases. Ensure every phrase is a single translation unit with numbered placeholders rather than concatenated `<span>` fragments in .phtml, otherwise the ar_SA translator cannot reorder them.

### `search-controls-no-focus-indicator` — The primary site search input, the mobile search input and the header category select kill their focus outline and replace it with nothing

**Evidence.** make.js three literals: (1) header category select `className: "bg-white/10 text-white/80 text-xs px-3 py-2.5 border-r border-white/15 focus:outline-none cursor-pointer min-w-[130px] appearance-none"`; (2) desktop search `className: "flex-1 bg-white text-[#1a1a2e] text-sm px-4 py-2.5 focus:outline-none placeholder:text-gray-400 min-w-0"`; (3) mobile search `className: "flex-1 bg-white text-sm px-3 py-2.5 focus:outline-none"`. None carries a ring, border-change or outline replacement. The compiled rule is `.focus\:outline-none:focus{--tw-outline-style:none;outline-style:none}`.

**Impact.** Across the whole 460 KB bundle there are exactly 20 uses of `focus:outline-none`; 13 pair it with `focus:ring-2 focus:ring-blue-500`, 4 with a 1px border-colour swap, and these 3 pair it with nothing at all. A keyboard or switch user tabbing into the site header cannot tell whether focus is on the category select, the search field, or the search button. This is the single most-used control on a marketplace storefront.

**Fix.** Remove `focus:outline-none` from all 20 occurrences. If a custom indicator is wanted, use `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0f2144]` — never `outline-none` without a same-declaration replacement. Also drop `appearance-none` from the header select or add a visible chevron: it currently has no dropdown affordance at all.

### `seller-onboarding-absent` — No seller registration, seller login or seller dashboard anywhere — every CTA dead-ends on a marketing page

**Evidence.** Vendors page CTA: `C, { to: "/about", ... children: "Become a Seller on ME Commerce" ... "Start Selling " }` (make.js:10289-10310). Footer: `{ title: "Sellers", links: [["Start Selling", "/vendors"], ["Seller Dashboard", "/vendors"], ["Commission", "/features"], ["Payouts", "/features"], ["Seller Academy", "/about"]] }`. Top bar: `C, { to: "/vendors", ... children: "Sell on MECommerce" }`.

**Impact.** Supply side of the marketplace has zero designed surface. Vendors cannot sign up, log in, or manage anything. The Platform page describes the vendor panel in prose (`"Upload and edit product listings with variants"`, `"Request or schedule payouts"`) but nothing is drawn.

**Fix.** Add at minimum: seller registration with KYC/trade-licence upload and country selection; seller login (separate from customer login); and the seller panel shell — dashboard, products list + product add/edit, orders, shipments, payouts/statements, store profile & policies, reviews.

**Magento.** These are real Vnecoms routes: /marketplace/* for the panel, vendor account create/login, and vendor store settings. The Platform page mockup at make.js:10600+ shows a `"Vendor Dashboard"` panel with `"Pending Payout"` `"AED 3,820"` — treat that thumbnail as the spec brief, not the design.

### `three-parallel-design-systems` — The 14 pages run on three mutually exclusive visual systems, and they collide inside single pages

**Evidence.** Per-page hex/utility census over the route components (routes at make.js:11044-11061):
• Brand system (navy #0f2144 + orange #f26522, `rounded-xl`) — Home `pc` (54 brand hex hits, 0 blue), Category `bc` (25/0), Bundles `Or`/`Tc`/`Lc` (29/0)
• Editorial system (near-black #0a0a0a + terracotta #c85c2c + warm greys #9e9890/#6b6560/#f6f4f1, square corners) — Product `fc` (#0a0a0a ×26, #c85c2c ×19), Vendors `Cc` (17/16), Features `jc` (12/14), Platform `Ec` (21/17). Zero brand hex in any of them.
• Default-Tailwind system (`blue-600`/`purple-600`, `rounded-lg`) — Search `xc`, Cart `yc`, Checkout `vc`, VendorProfile `wc`, CustomerProfile `Nc`, About `kc`, NotFound `Ac`. 61 × `blue-600`, 16 × `blue-700`, 14 × `blue-500`, plus `"bg-gradient-to-br from-blue-600 to-purple-600"` (9890, 10001) and `"text-9xl font-bold text-blue-600"` on the 404. Zero brand hex in any of them.

They collide: the Product page (`px-6`, #0a0a0a/#c85c2c, square) renders brand-system cards inside itself — `"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6", children: g.map((E) => t(tt, { product: E }, E.id))` (8353) — orange/navy `rounded-xl` cards on a terracotta page. Same at Search (8712) and VendorProfile (9422): brand cards inside blue pages.

**Impact.** Add-to-cart is orange on Home, near-black on the PDP, and blue in the wishlist. A customer cannot learn what "the button colour" means. Half the buy funnel (cart → checkout → confirmation) is in a colour scheme that appears nowhere in the brand.

**Fix.** Pick one system — the brand navy/orange one, since it owns Home/Category/Bundles — and instruct Figma Make to restyle Product, Vendors, Features, Platform, Search, Cart, Checkout, VendorProfile, CustomerProfile, About and NotFound to it. Delete every `blue-*`, `purple-*`, `#0a0a0a` and `#c85c2c` occurrence. Keep #c2410c as the AA-safe text/link tone and #f26522 for fills.

**Magento.** Cart, Checkout, My Account and 404 are exactly the pages Magento ships with its own markup (`Magento_Checkout`, `Magento_Customer`, `Magento_Cms`). If the design hands over blue-600 for those, the theme build will either re-skin core checkout in a colour nobody signed off, or ship a storefront where core pages silently fall back to Luma. Resolve this before scaffolding `app/design/frontend/`.

### `token-layer-is-dead-code` — The entire :root design-token layer has ~zero consumers — 100% of component colour is hardcoded arbitrary hex

**Evidence.** make.css declares 33 semantic tokens: `:root{--font-size:16px;--background:#fff;--foreground:#1a1a2e;--card:#fff;--card-foreground:#1a1a2e;--popover:#fff;--popover-foreground:#1a1a2e;--primary:#0f2144;--primary-foreground:#fff;--secondary:#f5f7fa;--secondary-foreground:#1a1a2e;--muted:#f5f7fa;--muted-foreground:#6b7280;--accent:#f26522;--accent-foreground:#fff;--destructive:#c0392b;--destructive-foreground:#fff;--border:#00000017;--input:transparent;--input-background:#f6f4f1;--switch-background:#c9c4bc;--ring:#0003;...--sidebar:#f6f4f1;...}`. Tailwind emitted 72 utility classes that reference those tokens (`.bg-accent{background-color:var(--accent)}`, `.text-primary{color:var(--primary)}`, `.bg-card{background-color:var(--card)}`, `.text-muted-foreground{color:var(--muted-foreground)}`, `.border-input`, `.bg-destructive`, `.fill-primary`, `.ring-offset-background`…). Grepping the 460 KB app bundle for those class strings returns **0** for every one: `bg-primary` 0, `bg-accent` 0, `text-primary` 0, `bg-muted` 0, `text-muted-foreground` 0, `bg-card` 0, `bg-destructive` 0, `border-border` 0, `bg-input-background` 0, `bg-sidebar` 0. Script check: 65 of the 72 token-referencing classes have no consumer (the 7 'hits' are substring false-positives on the words `hover`, `focus`, `file`, `dark`, `*`). The ONLY live token consumers in the whole build are two base-layer rules: `body{background-color:var(--background);color:var(--foreground)}` and `*{border-color:var(--border);outline-color:var(--ring)}`. Everything else is arbitrary-value: `text-[#f26522]` ×49, `text-[#0a0a0a]` ×43, `text-[#0f2144]` ×36, `text-[#c85c2c]` ×35, `text-[#9e9890]` ×35, `bg-[#f6f4f1]` ×20, `border-[#f26522]` ×19, `border-[#0a0a0a]` ×19, `text-[#6b6560]` ×18, `bg-[#c85c2c]` ×16, `bg-[#0f2144]` ×16, `bg-[#f26522]` ×14, `bg-[#0a0a0a]` ×14. No `var(--…)` appears anywhere in make.js (0 matches).

**Impact.** There is no working theme layer. Changing `--accent` changes nothing on screen; changing `--primary` changes nothing. Anyone told 'the tokens are in :root' will restyle the CSS variables, see no visual change, and then hand-edit ~470 inline hex values across 15 pages. Rebranding, dark mode, per-store-view theming and white-labelling are all impossible without a full rewrite of every className string.

**Fix.** Instruct Figma Make to: (1) map every semantic token through `@theme inline { --color-primary: var(--primary); --color-accent: var(--accent); … }` so Tailwind generates real `bg-primary`/`text-accent` utilities; (2) replace EVERY `-[#hex]` arbitrary value in components with the corresponding token utility; (3) add the missing tokens this design actually needs (see the commerce-semantics finding) rather than inventing new hex. Acceptance test: after the change, `grep -c '\[#' bundle.js` must be 0 and changing `--primary` in :root must recolour the whole site.

**Magento.** This is the single biggest blocker to a clean Magento 2.4.8 port. Magento theming is variable-driven (`web/css/source/_theme.less` overriding `@color-*`, `@primary__color`, `@link__color`). If the hand-off arrives as 470 hardcoded hexes there is nothing to map into `_theme.less`, and every store view / vendor skin will require duplicated CSS. Demand a token-clean build before starting the theme; budget the token mapping as its own phase.

### `tokens-declared-never-used` — The design-token layer is decorative: 0 of ~390 colour classes in the app reference a token

**Evidence.** make.css `:root` declares the full semantic set — `--primary:#0f2144;--secondary:#f5f7fa;--muted:#f5f7fa;--muted-foreground:#6b7280;--accent:#f26522;--destructive:#c0392b;--border:#00000017;--input-background:#f6f4f1;--ring:#0003;--radius:.75rem` — and the compiled CSS *does* emit the utilities that consume them (`var(--accent)` ×18, `var(--primary)` ×16, `var(--destructive)` ×32, `var(--radius)` ×25). But make.js uses none of them: grep for `bg-primary|bg-accent|bg-secondary|bg-muted|text-primary|text-accent|text-muted-foreground|border-border|bg-card|bg-destructive|ring-ring` returns **0 hits**. Instead: 394 arbitrary-value colour classes (`text-[#f26522]` ×49, `text-[#0a0a0a]` ×43, `text-[#0f2144]` ×36, `text-[#c85c2c]` ×35, `bg-[#f6f4f1]` ×20 …), 64 distinct hex literals in the JS, and 464 uses of Tailwind's default palette (`gray` ×249, `blue` ×107, `green` ×30, `red` ×27, `amber` ×25, `yellow` ×10, `purple` ×10). Ten distinct grey steps are used (`gray-50` through `gray-900`); none equals the declared `--muted-foreground:#6b7280`.

**Impact.** Nothing is themeable. Changing the accent means a find-and-replace across 394 class strings. There is no dark mode, no per-store-view palette, and no way to give the Arabic store view a different treatment. The declared destructive (#c0392b) is never used — errors render as `red-500`/`red-600`.

**Fix.** Instruct Figma Make to map every colour to the semantic utilities it already compiles: `bg-primary`, `text-accent`, `bg-muted`, `border-border`, `text-destructive`, `bg-card`. Add the missing tokens the app actually needs (`--accent-hover`, `--rating`, `--success`, `--price`, `--price-was`) rather than inventing hexes at the call site. Zero raw hex in component markup is the acceptance criterion.

**Magento.** Magento themes vary by store view via LESS variables (`_theme.less`) or, for Hyvä, Tailwind CSS custom properties. Tokenised classes map 1:1 onto that; 394 hard-coded hexes do not — they would have to be re-derived by hand, and every subsequent design change would need a full re-audit.

### `two-competing-brand-systems-split-by-page` — Two mutually exclusive colour systems (cool navy/orange vs warm black/terracotta) are split across pages; Platform runs both at once

**Evidence.** Attributing every hex in make.js to its page component (route table at line 11042: `{path:"product/:id",Component:fc}`, `{path:"vendors",Component:Cc}`, `{path:"features",Component:jc}`, `{path:"platform",Component:Ec}` …) gives two disjoint sets.
SYSTEM A — cool navy/orange: `#f26522` Layout:31 Home:32 Category:15 Platform:16 · `#0f2144` Layout:15 Home:22 Category:10 Platform:13 · `#f5f7fa` Layout:1 Home:2 Category:1 Platform:2.
SYSTEM B — warm near-black/terracotta: `#0a0a0a` Product:26 Vendors:17 Features:13 Platform:21 · `#c85c2c` Product:19 Vendors:16 Features:14 Platform:17 Layout:1 · `#9e9890` Product:9 Vendors:8 Features:3 Platform:15 · `#6b6560` Product:5 Vendors:4 Features:4 Platform:5 · `#f6f4f1` Product:5 Vendors:4 Features:2 Platform:9.
The two sets never co-occur on Home/Category (0 warm hex) or Product/Vendors/Features (0 `#0f2144`, 0 `#f26522`). Platform (Ec) contains both: `bg-[#0f2144]`+`#f26522` AND `bg-[#0a0a0a]`+`#c85c2c`. Same UI element, two colours: PDP add-to-cart is `"bg-[#0a0a0a] text-white hover:bg-[#c85c2c]"` (line 8286) while the header search button is `"bg-[#f26522] hover:bg-[#d9561d] text-white px-6 py-2.5"`. Verified-vendor tick is `text-[#f26522]` on Home (lines 7734, 7957, 8008) but `text-[#c85c2c]` on PDP/Vendors (lines 8310, 10152, 10252). Combined literal counts: #f26522 138, #c85c2c 95, #0a0a0a 94, #0f2144 72.

**Impact.** A shopper who lands on Home (navy header, orange CTAs, cool #f5f7fa page) and clicks a product arrives on a page with a warm #f6f4f1 surface, near-black CTAs and terracotta accents — while the sticky navy header stays on screen above it. It reads as two different websites glued together. Establishing facts also need correcting: #c85c2c is NOT a minor 'accent hover' (established note says x10) — it appears 95 times and is the PRIMARY accent on 4 of 15 pages; #0a0a0a is not an outlier (94 uses) but the primary foreground/CTA fill on those same pages.

**Fix.** Force a single decision before implementation: keep System A (navy #0f2144 + orange accent + cool neutrals) as the brand and rewrite Product, Vendors, Features and the warm half of Platform onto it — i.e. `#0a0a0a`→`--foreground`, `#c85c2c`→accent token, `#9e9890`/`#6b6560`→`--muted-foreground`, `#f6f4f1`→`--muted`. Delete the warm ramp entirely. Ask Figma Make to prove it by shipping a build where Product and Home screenshot with identical header, CTA and body-text colours.

**Magento.** Product/Vendors/Features map to the highest-traffic Magento templates (`Magento_Catalog::product/view`, Vnecoms vendor pages, CMS pages). If the split ships, `_theme.less` needs two parallel colour blocks scoped by body class (`.catalog-product-view`, `.vendors-index-index`), which doubles the LESS and guarantees drift. Resolve in design, not in LESS.

### `unbranded-pages-use-tailwind-default-blue-as-primary` — Six pages contain zero brand colour — Tailwind's default blue-600 (#155dfc) is the de-facto primary across the whole checkout funnel

**Evidence.** Cart(yc 8786), Checkout(vc 8962), Search(xc 8579), VendorProfile(wc 9306), CustomerProfile(Nc 9510) and About(kc 9888) contain **no** `#0f2144`, `#f26522`, `#0a0a0a` or `#c85c2c` at all — the per-page hex attribution shows those pages absent from every brand-hex bucket. They are styled entirely from Tailwind's stock palette: `blue-600` ×61 (`bg-blue-600` 18, `text-blue-600` 30, `border-blue-600` 9), `gray-600` ×70, `gray-50` ×39, `gray-400` ×36, `gray-100` ×33, `gray-200` ×22, `blue-700` ×16, `blue-500` ×14. Tailwind default-palette utility hits per page: CustomerProfile 71, Checkout 52, VendorProfile 45, About 45, Cart 29, Search 24 (vs Product 6, Features 3). Concrete markup: line 8925 `className: "w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 flex items-center justify-center gap-2 mb-3"` (Proceed to Checkout); line 9095 `"w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700"` (Continue to Payment); line 8626 `` `pb-2 px-1 ${i === "vendors" ? "border-b-2 border-blue-600 text-blue-600 font-medium" : "text-gray-600"}` ``; line 9340 `t(re,{className:"w-7 h-7 text-blue-600"})` (verified vendor tick); line 9836 `"bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"` (Save Changes). blue-600 resolves from `--color-blue-600:oklch(54.6% .245 262.881)` = **#155dfc**, a colour that appears nowhere in the brand spec.

**Impact.** The entire conversion path — cart, checkout, account, vendor profile — is stock Tailwind. The brand disappears exactly where trust matters most, and the primary CTA changes colour between the product page and the cart. This is not a 'polish later' issue: it means those six pages have never actually been designed, only wireframed.

**Fix.** Tell Figma Make these six pages are unstyled and must be re-skinned on the same token set as Home: every `bg-blue-600`→`bg-primary`, `text-blue-600`→`text-primary` (or accent for links), `hover:bg-blue-700`→primary-hover token, `text-gray-600`→`text-muted-foreground`, `bg-gray-50`→`bg-muted`, `border-gray-100/200/300`→`border-border`. Then re-review — the layout on those pages is also likely placeholder.

**Magento.** Cart/Checkout are `Magento_Checkout` (Knockout templates) and CustomerProfile is `Magento_Customer` — the hardest areas to restyle in Magento and the ones where a designer's unbranded stub costs the most. Get real designs for these before scoping. Vnecoms vendor profile/dashboard styling is also affected.

### `vendor-card-9-renderings` — Nine separate vendor/seller card renderings, zero shared component — critical for a Vnecoms build

**Evidence.** No vendor component exists (the only reusable components in the bundle are `tt` ProductCard, `Ya` BundleCard, and `Sa` panel frame at 10528). Nine hand-rolled renderings:
1. Home "Featured Stores" (7722) — circular `"w-14 h-14 rounded-full object-cover border-2 border-gray-100"` logo, lucide check overlay, star + rating, product count + hard-coded `"30–60 min"`, `"Visit Store"` pill
2. Home "Top Vendors This Month" (7946) — `h-24` banner + `"w-12 h-12 rounded-xl … -mt-8"` logo, `"#1 Vendor"` amber badge on index 0 only, `"· {n} products"`
3. Home "New Stores" (7991) — dark, `"w-11 h-11 rounded-full"`, no banner, verified as text `"✓ Verified"` in `text-[9px] uppercase`
4. PDP vendor box (8303) — square `"w-11 h-11 object-cover"` logo, rating as literal text `u.rating, " ★  ·  ", u.totalProducts, " products"`
5. Search vendors tab (8720) — `h-24` banner + `"w-12 h-12 rounded-full"` (no negative offset), rating as `o.rating, " ★ • ", o.totalProducts`
6. VendorProfile header (9330) — `"w-32 h-32 rounded-2xl border-4 border-white shadow-xl"` over `h-64` banner, verified as `"w-7 h-7 text-blue-600"`
7. Vendors spotlight (10131) — full-bleed `h-56` dark, `"w-16 h-16 object-cover border-2 border-white/20"`, verified as bordered chip `"text-[10px] tracking-widest uppercase text-[#c85c2c] border border-[#c85c2c]/40 px-2 py-0.5"`
8. Vendors grid (10237) — square `"w-14 h-14 … border-2 border-white"`, verified as white chip, star `"fill-[#c85c2c] text-[#c85c2c]"`
9. Cart vendor group header (8829) — bare `"font-semibold text-blue-600 hover:text-blue-700"` link
Logo shape alone is circle / rounded-xl / rounded-2xl / square across the nine. "Verified" has five different visual treatments.

**Impact.** Sellers are the product in a marketplace, and their identity is rendered nine different ways. A vendor cannot recognise their own store card between the homepage rail, the search tab and the directory. The "verified" trust signal — the single most important badge on the platform — has no consistent form, so it reads as decoration rather than a status.

**Fix.** One VendorCard with props: `layout: 'tile' | 'row' | 'banner' | 'spotlight'`, `logoShape: 'circle' | 'square'` (pick one and hard-code it), `showBanner`, `showDescription`, `showStats`, `rank?`, `cta?`. One Verified badge component with a single form used everywhere. Delete the hard-coded `"30–60 min"` or make it a real prop.

**Magento.** Vnecoms renders seller info through `Vnecoms\Vendors\Block\Vendor\*` in several contexts (product page seller box, seller list, seller page header, cart per-seller group). Nine designs means nine template overrides against Vnecoms blocks — each one a merge conflict on every Vnecoms upgrade. Specify one card and one badge before implementation.

### `zero-logical-css-65-physical-utilities` — Zero logical CSS properties anywhere: 65 hard physical direction utilities, no `rtl:`/`ltr:` variants, no `[dir=rtl]` rules in the compiled CSS

**Evidence.** make.js contains 65 physical-direction class instances and 0 logical ones. Exact tally: `ml-*`×17 (`ml-auto`×5, `ml-0.5`×4, `ml-2`×3, `ml-1`×2, `ml-0`, `ml-1.5`, `ml-3`), `mr-*`×2, `pl-*`×2 (`pl-12`, `pl-9`), `pr-*`×6 (`pr-8`×2, `pr-4`×2, `pr-2`, `pr-10`), `left-*`×15, `right-*`×16, `border-r`×3, `border-l`×1, `text-left`×2, `divide-x`×1.
Regex for `ms-|me-|ps-|pe-|start-|end-` → 0 matches. Regex for `rtl:` / `ltr:` variants → 0 matches.
make.css confirms these compile physically: `.ml-auto{margin-left:auto}`, `.pl-12{padding-left:calc(var(--spacing)*12)}`, `.left-3{left:calc(var(--spacing)*3)}`, `.text-left{text-align:left}`, `.border-r{border-right-style:var(--tw-border-style);border-right-width:1px}`. Search of the 130 KB stylesheet for `[dir=`, `:dir(`, `direction:rtl`, `unicode-bidi` → 0 hits each.
Per page: Layout/product-cards 19, Home 17, Vendors 7, Category 5, Search 4, Product 4, CustomerProfile 4, Platform 4, Features 1.
(Note: `divide-x` and `px-*`/`mx-*` DO compile logically — `.divide-x>:not(:last-child){border-inline-start-width:…}`, `.px-4{padding-inline:…}` — so those are safe.)

**Impact.** Setting `dir="rtl"` flips text and flex order but leaves all 65 of these anchored to the physical left/right, producing a half-mirrored layout: absolute badges, icon overlays, dividers and auto-margins all stay where the LTR design put them while everything around them moves.

**Fix.** Global find-and-replace to Tailwind logical utilities: `ml-*`→`ms-*`, `mr-*`→`me-*`, `pl-*`→`ps-*`, `pr-*`→`pe-*`, `left-*`→`start-*`, `right-*`→`end-*`, `border-l`→`border-s`, `border-r`→`border-e`, `text-left`→`text-start`. Then set the design canvas to `dir="rtl"` and re-review every page.

**Magento.** Whatever LESS/CSS the theme emits must be direction-agnostic in the same way. Magento's Luma/blank parents are not RTL-safe out of the box, so plan on either logical properties throughout the custom LESS or a build-time RTLCSS pass keyed to the ar store view.


## P1

### `accent-orange-as-text` — #f26522 is used as text or as a text background 62 times and never clears 4.5:1 on any surface it appears on

**Evidence.** 49 × `text-[#f26522]`, 13 × `bg-[#f26522]` with `text-white`. Measured: #f26522 on #fff = 3.15:1; on the `#f5f7fa` page background = 2.94:1; on `#f6f4f1` input surface = 2.87:1; on the `#fff8f5` promo band = 3.00:1. White on #f26522 = 3.15:1, and the hover `bg-[#d9561d]` only reaches 3.96:1. Worst instances: hero badge `"inline-flex items-center text-white text-[10px] font-bold tracking-widest uppercase px-3 py-1 rounded-full w-fit mb-4"` over `backgroundColor: "#f26522"`; cart count badge `"absolute -top-1.5 -right-1.5 bg-[#f26522] text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center"`; vendor chip `"text-[11px] font-semibold text-[#f26522] border border-[#f26522]/30 px-3 py-1 rounded-full"` (border itself = 1.41:1); product-card vendor name `"text-[10px] text-[#f26522] font-semibold mb-1 truncate"`. The de-facto hover `#c85c2c` reaches only 4.18:1 on white and 3.81:1 on #f6f4f1.

**Impact.** The accent is the brand's primary call-to-action colour and its primary link colour, and at 9–11px it is the least readable text on the light pages. Fails 1.4.3 for every text use and 1.4.11 for the /30 and /20 borders.

**Fix.** Adopt the agreed split and extend it: `#f26522` for fills only (large solid areas, icons ≥24px, decorative rules), `#c2410c` for all accent TEXT and links — it measures 5.18:1 on white, 4.83:1 on #f5f7fa and 4.72:1 on #f6f4f1, so it passes on all three site surfaces. For white-on-orange badges use `#b54e24` (already in the palette) or darker; do not use white on `#f26522` at 9–10px. Replace the /30 and /20 accent borders with a solid `#c2410c` at 1px.

**Magento.** Define `@color-accent-fill: #f26522` and `@color-accent-text: #c2410c` as two separate LESS variables in the theme's _theme.less. Magento core mixins (`.lib-link()`, `.lib-button-primary()`) take a text colour and a background colour separately, so the split maps cleanly — but only if the two are never aliased to each other.

### `accent-orange-five-variants` — Five near-duplicate oranges pretending to be one accent, with three different 'hover' oranges

**Evidence.** #f26522 (138 total: 44 css incl. alpha variants #f265221a/#f2652233/#f2652240/#f265224d/#f2652266, 94 js) · #c85c2c (95) · #d9561d (7) · #b54e24 (3) · #f9884a (3). Deltas (RGB euclidean): #f26522↔#c85c2c 44, #f26522↔#d9561d 30, #c85c2c↔#d9561d 23, #c85c2c↔#b54e24 25, #f26522↔#f9884a 54. All three hover pairings coexist: `"bg-[#f26522] hover:bg-[#d9561d]"` (header search, product cards, bundle CTA), `"bg-[#0a0a0a] text-white hover:bg-[#c85c2c]"` (PDP), `"bg-[#c85c2c] … hover:bg-[#b54e24]"` (Vendors 10299), `"text-[#f26522] hover:text-[#f9884a]"` (header Bundle Deals link 5704). Contrast on white: #f26522 3.15, #d9561d 3.96, #c85c2c 4.18, #b54e24 5.15, #f9884a 2.43.

**Impact.** Hover feedback is inconsistent in both direction and magnitude — some hovers darken by 23 units, others by 54, one *lightens* (#f9884a, 2.43:1, effectively invisible on white). A user cannot read hover as a single affordance, and QA has no single value to check.

**Fix.** One accent + one derived hover: `--accent:#f26522`, `--accent-hover:#c2410c` (or a single systematic -12% lightness step). Delete #c85c2c, #d9561d, #b54e24, #f9884a. Never lighten on hover against a light background.

**Magento.** Two LESS variables (`@accent__color`, `@accent__color__hover`) plus a mixin — trivial once the design collapses. If it does not collapse, expect the hover colour to drift again the first time a Vnecoms vendor template is added.

### `account-collapsed-into-one-route` — The entire customer account is drawn as one /profile route with 4 client-side tabs; Magento has 8+ separate routes and half the sections are missing

**Evidence.** make.js:9510 `const [e, a] = z("orders")` with tabs `"My Orders"`, `"Wishlist"`, `"Addresses"`, `"Settings"`. Router has a single `{ path: "profile", Component: Nc }`. `grep -c` returns 0 for `store credit`, `RMA`, `Return Request`, `Quote`, `RFQ`, `Reorder`, `Order Details`.

**Impact.** Six account areas the marketplace needs have no design at all: order detail view, returns/RMA, store credit / wallet, my product reviews, newsletter subscriptions, and saved payment methods. 'View Details' and 'Track Package' buttons (make.js:9699-9701) point nowhere.

**Fix.** Keep the sidebar-card visual, but design it as Magento's persistent `customer_account_navigation` wrapping distinct pages, and add: order view, returns list + request form, store credit balance + transaction history, my reviews, newsletter preferences. Also design the logged-out redirect state.

**Magento.** Native targets: customer/account/index, sales/order/history, sales/order/view, wishlist/index/index, customer/address/index, customer/account/edit, newsletter/manage, review/customer/index. Rebuilding these as SPA tabs means a custom controller aggregating all of them — the sidebar-nav approach costs nothing and is what the theme already supports.

### `arabic-line-metrics` — Heading line-heights of 1.0–1.25 and fixed min-heights on clamped titles will clip Arabic ascenders, descenders and diacritics

**Evidence.** Base: `h1{...line-height:1.15}`, `h2{...line-height:1.2}`, `h3{...line-height:1.3}`. Inline: `lineHeight: 1.08` ×2 (Features, Platform h1), `lineHeight: 1.1` (Vendors h1), `lineHeight: 1.15` ×2. Utilities: `leading-none` (`.leading-none{line-height:1}`) ×4, `leading-tight` (`--leading-tight:1.25`) ×11 — e.g. `"text-white text-2xl md:text-4xl font-bold max-w-sm mb-3 leading-tight drop-shadow-md"` and `"text-white font-bold text-lg leading-tight"` ×3 on the promo tiles. The arbitrary sizes set no line-height at all — `.text-\[9px\]{font-size:9px}` — so they inherit whatever factor is in scope. Card titles are clamped against fixed heights: `line-clamp-2` ×13 with `min-h-[2rem]` and `min-h-[2.5rem]`.

**Impact.** Arabic Naskh needs materially more vertical room than Latin: tall ascenders (ك ل ط ظ), deep descenders (ج ح خ ع غ م ن ي س ش), and harakat/nuqat that sit above and below the letter body. A 1.0–1.25 line box clips them and, in stacked lines, the descenders of one line collide with the ascenders of the next. The `min-h-[2rem]` + `line-clamp-2` pairing was tuned against a 12px/1.375 Latin line box (16.5px × 2 = 33px, already 1px over the 32px min-height); an Arabic line box at the same font-size is taller still, so titles crop mid-glyph.

**Fix.** For `:lang(ar)`: floor display line-height at 1.35 (against 1.08–1.2 Latin), body at 1.7 (against 1.5/1.625), and increase font-size ~1.1× — or set `font-size-adjust` against the chosen Arabic face so both scripts share an optical size. Replace fixed `min-h-[Nrem]` on clamped titles with `min-height: calc(2 * 1em * var(--line-height))` so it follows the locale. Give the arbitrary sizes explicit line-heights (or delete them per the sub-14px finding).

**Magento.** Put the `:lang(ar)` metric overrides in one block in `_typography.less` — line-height floor, size multiplier, `letter-spacing: 0` — so they apply to Vnecoms vendor blocks and third-party widgets too, not just theme-authored components.

### `arabic-strings-without-lang` — Two Arabic strings render inside a `lang="en"` document with no `lang` attribute of their own

**Evidence.** make.html sets `<html lang="en">`. make.js contains exactly two Arabic literals: `t("span", { children: i ? "English" : "عربي" })` in the top utility bar, and `t("button", { onClick: g, className: "block py-1 text-white/65 hover:text-white text-left w-full", children: i ? "Switch to English" : "التحويل إلى العربية" })` in the mobile menu. `grep -c 'lang:' make.js` = 0 — the only lang handling is the toggle itself: `document.documentElement.dir = p ? "rtl" : "ltr", document.documentElement.lang = p ? "ar" : "en"`.

**Impact.** Fails WCAG 3.1.2 Language of Parts. In the default English state the language switcher is the one control a non-English speaker needs, and a screen reader will pronounce "عربي" using the English voice — producing unintelligible noise rather than the word "Arabic". The same applies in reverse. Note also that the toggle only flips `dir`/`lang` on `<html>`; no UI copy is translated, so in the "Arabic" state a `lang="ar"` document is full of English text — the mirror-image 3.1.2 failure at full-page scale.

**Fix.** Add `lang="ar"` and `dir="rtl"` to the two Arabic-bearing spans/buttons. In the real implementation, the switcher must be a link to the Arabic store view, not a client-side attribute flip.

**Magento.** Magento store-view switching handles `<html lang>` and `dir` correctly out of the box via `Magento_Store`; do not port this JS toggle. Wire the switcher to `store/switch` with the `ar_SA` store view. This also matters because DM Sans and Playfair Display carry no Arabic glyphs — an Arabic store view needs a separate font stack declared in the ar_SA theme.

### `badge-system-20-adhoc` — About twenty ad-hoc badge treatments, no Badge component, and the discount badge uses an undeclared red

**Evidence.** No badge component exists. Discount badges alone come in five spellings: `"absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm"` (5827), `"absolute top-2 left-2 bg-red-500 … text-[9px] … px-1.5 py-0.5 rounded-full"` (5786), `"absolute top-2.5 left-2.5 bg-red-500 … text-[10px] px-2 py-0.5 rounded-full"` (7636), `"absolute top-1.5 left-1.5 bg-red-500 … text-[9px] px-1.5 py-0.5 rounded-full"` (8531), and for bundles `"absolute top-1.5 left-1.5 bg-[#f26522] …"` (5886) / `"bg-[#f26522] text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow"` (5932). `bg-red-500` (#ef4444) is not the declared `--destructive:#c0392b`, and it is used as a *promotional* colour, not an error colour.
Other badge families with no shared base: bundle badge `"bg-[#0f2144] text-white text-[10px] … rounded-full shadow"` (5937) vs `"bg-[#0f2144] text-white text-xs font-bold px-3 py-1 rounded-full"` (10836); rank pip `"absolute top-2 left-2 w-5 h-5 rounded-full … text-[10px]"` with `style: { backgroundColor: N === 0 ? "#f59e0b" : N === 1 ? "#94a3b8" : N === 2 ? "#b45309" : "#0f2144" }` (7866); `"#1 Vendor"` `"bg-amber-400 text-amber-900 text-[9px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded"` (7952, the only non-pill badge); order status `"bg-green-100 text-green-700" : "bg-blue-100 text-blue-700" : "bg-yellow-100 text-yellow-700"` (9652); savings `"text-xs font-bold text-green-600"` (5972) vs `"bg-green-100 text-green-700 text-sm font-bold px-3 py-1 rounded-full"` (10894); out-of-stock `"bg-gray-800 text-white text-xs font-bold px-3 py-1.5 rounded-full"` (5830); item-count `"bg-black/50 backdrop-blur-sm text-white text-[10px] font-semibold px-2 py-1 rounded-full"` (5939); eyebrow `"text-[10px] tracking-widest uppercase text-[#c85c2c]"` (editorial pages) vs `"inline-block bg-[#f26522] text-white text-[10px] font-bold tracking-widest uppercase px-3 py-1 rounded-full"` (8021).

**Impact.** There is no visual grammar for status. A red pill means "discount", a green pill means "saving", a green pill of a different shape means "delivered", a grey pill means "out of stock" — a customer has to decode each one. Nine near-identical size/padding combinations mean the badge shifts position and weight between adjacent cards.

**Fix.** One Badge with `tone: 'promo' | 'success' | 'info' | 'warning' | 'neutral' | 'inverse'` and `size: 'xs' | 'sm' | 'md'`, plus a `placement` prop for the absolutely-positioned card overlays (which must also become logical: `start`/`end`, not `left`/`right`). Move the discount promo tone off `red-500` onto a declared token. Collapse the five discount spellings into one.

**Magento.** Magento product labels are usually a theme-level partial applied in the product-item template; Vnecoms adds its own seller badges. One Badge partial with a tone modifier keeps that to a single LESS block instead of twenty.

### `broken-electronics-category-link` — The Electronics category tile and hero tile point at a slug that does not exist, silently showing the whole catalogue

**Evidence.** The 8 pastel homepage tiles include `{ label: "Electronics", emoji: "📱", href: "/category/electronics", color: "#e8eaf6", items: "1,240+ items" }` (7385) and the hero side-tile set includes `{ label: "Electronics Deals", sub: "Up to 40% off", href: "/category/electronics" …}` (7374). But the category collection uses a different slug: `{ id: "1", name: "Electronics & Tech", slug: "technology", … productCount: 3065 }` (6045); the header nav agrees with the data — `{ label: "Electronics & Tech", href: "/category/technology", emoji: "💻" }` (5590). The Category page then does `a = Aa.find((w) => w.slug === e)` → undefined, `S = J.filter((w) => w.category === e)` → empty, and falls through to `S.length === 0 && (S = J)` (8368) — i.e. it renders the entire catalogue under a title derived from the slug (`T = a?.name ?? e?.replace(/-/g, " ")…`).

**Impact.** Two prominent homepage entry points land on a page titled "Electronics" that lists every product in the store, including groceries and pharmacy. The silent `S = J` fallback means this failure never surfaces as an error — it just quietly shows the wrong catalogue.

**Fix.** One slug per category, referenced from one place. Ask Figma Make to derive every category `href` from the category collection rather than typing slugs into three separate arrays (`ta`, `dc`, `cc`, `Aa`). Also remove the `S.length === 0 && (S = J)` fallback so a bad slug shows the empty state instead of the whole catalogue.

**Magento.** Magento resolves category URLs through `url_rewrite`; a bad slug 404s rather than silently showing everything. But the duplicated navigation arrays are the real warning — the design has four independent copies of the category list, and the Magento build must have exactly one (the category tree), rendered into the header, the homepage tiles and the footer by the same block.

### `broken-vendor-links-6-of-10` — Six of the ten vendor cards on /vendors link to a "Vendor not found" page

**Evidence.** The Vendors listing renders `Pt`, defined as `Pt = [...at, { id: "5", name: "Apex Sport" …}, { id: "6", name: "Lumière Beauty" …}, { id: "7", name: "BrainSpark Toys" …}, { id: "8", name: "Gulf Distributors" …}, { id: "9", name: "Al Meera Fresh" …}, { id: "10", name: "Noura Couture" …}]` (make.js:10018). The VendorProfile route resolves against the *other* array: `function wc() { const { id: e } = Ot(), a = at.find((s) => s.id === e) …}` (9306), where `at` (5999) contains only ids 1-4. Ids 5-10 therefore hit `if (!a) return … "Vendor not found"` (9310). The homepage "Seller Spotlight" also picks from `Pt`: `const m = Pt.find((u) => u.verified && u.rating >= 4.8) ?? Pt[0]` (10098).

**Impact.** 60% of the seller directory is a dead end. The Vendors page also advertises `{ val: \`${Pt.filter((u) => u.verified).length}\`, label: "Verified" }` — a count that includes sellers whose pages do not exist.

**Fix.** Tell Figma Make there must be exactly one vendor collection. Merge `at` and the six extras into a single array and have both the listing and the profile read from it. This is a data-contract bug that will be copied into the Magento fixture data if it is not caught now.

**Magento.** In the real build vendors come from `vnecoms_vendors` — one source — so the bug will not survive literal implementation. But it matters for design review: nobody has ever seen the profile page for 6 of the 10 designed sellers, so those layouts are unvalidated.

### `bundles-no-option-ui` — Bundle page is a fixed read-only kit with no option groups — the custom new_bundle type has nowhere to render selections

**Evidence.** Bundle items are `{ name, image, value }` only (make.js:7199+), rendered as a static checklist under `children: "What's Inside"` with `re` checkmark icons, and totalled as `children: "Total individual value"` against `a.originalPrice`. The CTA is `r("button", { className: "flex-1 bg-[#f26522] … ", children: [ t(rt, …), " Add Bundle to Cart" ] })` with no onClick and no qty-per-item.

**Impact.** If any bundle has selectable options (size, colour, choose-2-of-5), there is no designed UI for it: no option group headings, no radio/checkbox/select inputs, no required-field validation, no live price recalculation, no 'Customize and Add to Cart'.

**Fix.** Either (a) declare that all bundles are fixed kits — then every new_bundle must be configured with all options required and single-selection, and the option UI hidden; or (b) design the option-group UI and the dynamic bundle summary panel.

**Magento.** Magento's bundle view block renders option groups plus a live `#bundle-summary` price panel; the design replaces both with static text. Also, 'Individual value: $X' per item and the summed strike-through total are not native for a fixed-price bundle — children's standalone prices are not used in display and would need a custom block summing `getSelections()`.

### `carousel-autoplay-no-pause` — The hero carousel auto-advances every 4.5s with no pause control, and its slide dots are 6×6px unnamed empty buttons

**Evidence.** make.js: `Te(() => { const f = setInterval(() => a((N) => (N + 1) % ot.length), 4500); return () => clearInterval(f); }, [])`. Alongside it a second timer `setInterval(() => { n((N) => { ... }) }, 1e3)` drives the deals countdown. The dots: `t("button", { onClick: () => a(N), className: `h-1.5 rounded-full transition-all duration-300 ${N === e ? "w-6 bg-white" : "w-1.5 bg-white/40 hover:bg-white/60"}` }, N)` — no `children` key at all, so the element has no text node. There is no pause/play button, no `onMouseEnter` pause, and no `onFocus` pause anywhere in the file.

**Impact.** WCAG 2.2.2 requires a mechanism to pause, stop or hide any auto-updating content that starts automatically, lasts more than 5 seconds and runs in parallel with other content — both timers qualify. Users with cognitive or attention disabilities, and screen-magnifier users, lose their place every 4.5 seconds. The dots are 6×6px (inactive) and 24×6px (active), far below the 24×24px CSS-pixel minimum of WCAG 2.2 SC 2.5.8, and being empty `<button>` elements they announce as bare "button".

**Fix.** Add a visible pause/play toggle to the carousel; pause on `mouseenter`, `focusin` and when `prefers-reduced-motion: reduce` is set. Give each dot `aria-label={`Go to slide ${N+1} of ${ot.length}`}` and `aria-current`, and grow the hit area to at least 24×24 with a transparent `::before` while keeping the 6px visual dot.

### `carousel-controls-do-not-mirror` — Hero carousel prev/next are pinned `left-3`/`right-3` with non-mirroring ChevronLeft/ChevronRight, and the slide dots are pinned `bottom-4 left-8`

**Evidence.** Home hero (make.js 245,469 / 245,876):
```
t("button", { onClick: () => a((f) => (f - 1 + ot.length) % ot.length),
  className: "absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 bg-black/25 …",
  children: t(io, { className: "w-5 h-5" }) })            // io = I("chevron-left", …)
t("button", { onClick: () => a((f) => (f + 1) % ot.length),
  className: "absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 bg-black/25 …",
  children: t(no, { className: "w-5 h-5" }) })            // no = I("chevron-right", …)
```
Dots: `t("div", { className: "absolute bottom-4 left-8 flex gap-1.5", children: ot.map(…) })`.
SVG chevrons are inline `<path d="m15 18-6-6 6-6">` geometry — nothing mirrors them, and `transform: scaleX(-1)` is never applied (0 `translate-x`/`scale-x` utilities in the bundle).

**Impact.** In the Arabic view the "previous slide" control sits on the left pointing left and "next" sits on the right pointing right — both semantically backwards for an RTL reader, who expects previous on the right pointing right. The progress dots also read the wrong way: dot 1 (the current slide indicator) sits at the far left while the slide content starts at the right.

**Fix.** Change to `start-3`/`end-3` for the buttons and `bottom-4 start-8` for the dots, and mirror the glyphs with `rtl:-scale-x-100` (or swap the two icon components when `dir === 'rtl'`). Do not just swap positions without swapping glyphs — that produces a control pointing away from the direction it moves.

**Magento.** Same rule for any Slick/Owl/Splide carousel in the theme: set `rtl: true` on the slider config for the ar store view, or the track will animate the wrong way even if the buttons look right.

### `cart-missing-coupon-estimator-update` — Cart has no discount-code field, no shipping estimator, no update-cart action and no move-to-wishlist

**Evidence.** `grep -c 'Discount Code' make.js` = 0, `grep -c 'promo code' make.js` = 0, `grep -c 'Estimate Shipping' make.js` = 0. Cart totals are computed client-side: `s = l > 50 ? 0 : 9.99` and shown as `children: "Shipping"` / `"FREE"` with no address input. Per-item controls are only a trash button and `onClick: () => i(p.id, -1)` / `i(p.id, 1)`.

**Impact.** Coupons are advertised as a platform capability (`"Run promotions, coupons, and flash sales"`, make.js:10737) but there is nowhere on the storefront to enter one. And a shipping cost is displayed before any address exists, which Magento cannot do.

**Fix.** Add the discount-code accordion, an 'Estimate Shipping and Tax' block (country/region/postcode -> rate list), and per-item 'Edit' and 'Move to Wishlist' actions. Either add an explicit 'Update Shopping Cart' action or specify that qty changes auto-persist via AJAX.

**Magento.** Magento's cart totals come from the quote; a pre-address shipping figure has no source. The cheapest honest alternative is to show 'Shipping calculated at checkout' and keep the free-shipping progress nudge, which the design already has as `"🎉 You qualify for free shipping!"`.

### `cart-tax-label-hardcoded` — Cart hardcodes a 10% tax line labelled 'Tax (10%)' in a Gulf VAT marketplace

**Evidence.** make.js:8804 `c = l * 0.1` rendered as `children: "Tax (10%)"`. Checkout repeats it: `s = n * 0.1` under `children: "Tax"`. `grep -c 'VAT' make.js` = 3, all in marketing prose (`"Gulf VAT compliance"`, `"VAT-compliant"`) — never in the storefront UI.

**Impact.** 10% is not a rate in any target market (UAE/Bahrain/Qatar/Oman 5%, KSA 15%), and the label is wrong for the region. It also implies a single flat rate across five countries with different rates.

**Fix.** Relabel to 'VAT' and drop the inline percentage from the design, or specify that the percentage is rendered dynamically from the applied tax rate. Show the design at both 5% and 15% so the totals block is verified at both.

**Magento.** Magento's totals renderer prints the tax label without a rate; getting '(10%)' inline needs `tax/cart_display/full_summary` enabled plus a custom total renderer. Relabelling is free, the inline rate is not.

### `checkout-keeps-full-chrome` — Checkout renders inside the full 4-band header and full footer, which Magento's checkout layout deliberately strips

**Evidence.** The layout component wraps every route identically: make.js:5735 `t("main", { children: t(ss, {}) })` sits between the sticky `header` (top bar + logo/search band + `ta.map` category bar) and the full 6-column `footer`, and `/checkout` is a child route of it (`{ path: "checkout", Component: vc }`).

**Impact.** Checkout is shown with the full category mega-nav, search bar, wishlist/cart icons and the entire footer link farm — every one of which is an exit from the funnel. No reduced 'secure checkout' header state is designed.

**Fix.** Design a dedicated checkout chrome: logo, a 'Secure Checkout' lock/trust line, a back-to-cart link, and a minimal footer (support number, payment badges, T&C link). Nothing else.

**Magento.** `checkout_index_index.xml` already removes the top menu, header links and footer links in core, and Amasty OSC reduces it further — the design is asking to put back blocks the platform removes on purpose.

### `colour-literal-inventory` — Full inventory: ~140 rendered colours (76 distinct hex bases + 43 Tailwind palette steps + 32 alpha steps) against 33 declared tokens, of which only 2 rules actually consume tokens

**Evidence.** HEX IN make.css (count ×value): 35 #0000(transparent) · 34 #f26522 (+ alpha #f265221a/#f2652233/#f2652240/#f265224d/#f2652266 = 44 total) · 25 #0000001a · 24 #c85c2c (+ #c85c2c4d ×2, #c85c2c66 ×2 = 28) · 17 #0a0a0a · 14 #fff · 12 #0f2144 · 8 #ccc (all inside dead recharts selectors) · 6 #f6f4f1 · 6 #1a1a2e · 5 #c9c4bc · 5 #6366f1 (+ #6366f133, #6366f199 ×2) · 4 #f5f7fa · 4 #9e9890 · 4 #162d5a · 4 #0003 · 2 each #fff8f5 #f9884a #f7d24e #e0ddd8 #d9561d #d0cdc8 #b54e24 #6b6560 #2a1200 #1a4731 #1a3a6a #0d3320 #091830 #00000017 · 1 each #ede9e3 #c0392b #6b7280 #3a1a2e #1a2a0a #000; plus white-alpha #ffffff0d/0f/14/1a/1f/26/4d/59/73/80/8c/a6/b3/bf/e6 and black-alpha #0000000d/0f/14/1a/1f/26/40/4d/73/80.
HEX IN make.js: 94 #f26522 · 77 #0a0a0a · 67 #c85c2c · 60 #0f2144 · 35 #9e9890 · 20 #f6f4f1 · 18 #6b6560 · 8 #6366f1 · 6 #f5f7fa · 5 #d9561d · 5 #2d7a3a · 2 each #fdf5f9 #f59e0b #c9c4bc #a855f7 #9b4b7a #2a1200 #1a5fa8 #1a4731 #162d5a #0d3320 · 1 each #fff8f5 #fff8e1 #fff3e0 #fdf5f8 #fce4ec #faf7f4 #f9884a #f7d24e #f0f7f1 #ede7f6 #ec4899 #e8f5e9 #e8eaf6 #e3f2fd #e31e24 #e0ddd8 #d0cdc8 #c41e3a #b54e24 #b45309 #a50034 #9b2c5e #94a3b8 #8b5cf6 #7c5a3a #6b3fa0 #555 #3b1f6e #3a1a2e #1e3a6e #1a3a6a #1a2a0a #1a1a2e #1428a0 #12103a #10b981 #0f1b35 #0ea5e9 #0b5ed7 #091830 #080d1a #00d1ff #0080ff. (`#8841`/`#8839`/`#8835` are order-ID strings, not colours.)
RGB/RGBA/HSL: only 3 in the entire build — `rgb(from red r g b)` (Tailwind @supports probe, make.css), `rgba(200,200,200, 0.5)` (React-Router ErrorBoundary default, make.js:3733 — the customer-facing route-error screen is unstyled grey), and `hsl(var(--sidebar-border))`/`hsl(var(--sidebar-accent))` in two dead shadow utilities.
OKLCH USED AS REAL COLOUR (Tailwind palette, 43 distinct steps actually referenced by live utilities): gray-600 ×70, blue-600 ×61, gray-50 ×39, gray-400 ×36, gray-100 ×33, amber-400 ×23, gray-200 ×22, green-600 ×22, gray-500 ×19, blue-700 ×16, blue-500 ×14, gray-300 ×13, red-500 ×12, gray-700 ×11, blue-50 ×9, red-600 ×6, purple-600 ×6, blue-100 ×6, gray-800 ×5, yellow-400 ×5, green-100 ×4, red-50 ×3, green-700 ×3, red-400 ×2, red-300 ×2, yellow-100 ×2, purple-100 ×2, and 1 each: orange-50, purple-300, purple-900, amber-900, red-700, gray-900, orange-600, yellow-700, yellow-600, red-100, indigo-100, indigo-600, yellow-500, green-500, blue-400, amber-500. (Key hex equivalents: blue-600 #155dfc, gray-600 #4a5565, gray-400 #99a1af, gray-50 #f9fafb, amber-400 #ffb900, yellow-400 #fdc700, green-600 #00a63e, red-500 #fb2c36, purple-600 #9810fa, indigo-600 #4f39f6.) The 5 `--chart-*` and all `.dark` oklch values are shadcn defaults with 0 consumers.
ALPHA STEPS: 32 distinct — white {5,6,8,10,12,15,20,25,30,35,40,45,50,55,60,65,70,75,80,90}, black {5,6,8,10,12,15,20,25,30,45,50,60} across bg-/text-/border-/from-/divide-.
MAPPING: only #0f2144, #f26522, #1a1a2e, #fff, #f5f7fa, #6b7280, #c0392b, #00000017, #0003, #f6f4f1, #c9c4bc, #ede9e3, #0a0a0a correspond to a declared token — and of those, #6b7280, #c0392b and #ede9e3 appear ONLY in their own declaration and are never rendered.

**Impact.** About 140 distinct rendered colours against 33 declared tokens, with the token layer inert. Any estimate for the Magento build that assumes 'a token file exists' is wrong by an order of magnitude — this is a full colour re-specification, not a variable mapping.

**Fix.** Use this inventory as the reduction target: instruct Figma Make to get the build to ≤25 rendered colours (brand ramp 4 + accent 2 + neutral 5 + on-dark 3 + semantic 8 + pure black/white), with 0 arbitrary `-[#…]` values and 0 Tailwind default-palette classes. Re-run this count as the acceptance gate.

**Magento.** Present this table alongside the Magento estimate. The realistic sequence is: (1) design collapses to one system and one token set; (2) tokens map to `_theme.less` variables; (3) templates get built. Attempting (3) against the current build will hardcode ~140 colours into Magento templates, and the Arabic store view plus every Vnecoms vendor skin will inherit them permanently.

### `components-hand-rolled-not-reused` — Tabs, Select, Pagination, Breadcrumb, Progress and Carousel are hand-rolled while their shadcn equivalents sit compiled and unused

**Evidence.** Available in the CSS but never instantiated (see dead-CSS finding); hand-rolled instead:
• Tabs — 3 separate implementations: Search `` `pb-2 px-1 ${i === "products" ? "border-b-2 border-blue-600 text-blue-600 font-medium" : "text-gray-600"}` `` (8611/8623/8631); VendorProfile `` `pb-4 px-2 font-medium ${i === "products" ? "border-b-2 border-blue-600 text-blue-600" : "text-gray-600"}` `` (9388/9396/9404) — different padding, different active weight; CustomerProfile as a vertical sidebar `` `w-full px-6 py-4 flex items-center gap-3 border-b hover:bg-gray-50 ${e === "orders" ? "bg-blue-50 text-blue-600" : ""}` `` (9557+). None uses `role="tablist"`, `aria-selected` or arrow-key navigation (`onKeyDown` → 0 hits, `role:` → 0 hits).
• Select — 6 raw `<select>` with `appearance-none` plus a manually positioned chevron `<div>`; two of them (9415, 9623) have no `value`/`onChange` and are non-functional.
• Pagination — a literal string array: `["←", "1", "2", "3", "→"].map(…)` with `` `w-9 h-9 rounded-lg text-sm font-medium … ${w === "1" ? "bg-[#0f2144] text-white" : …}` `` (8566). Current page hard-coded to "1", no `aria-current`, no disabled prev/next, arrows as text glyphs that will not mirror in RTL.
• Breadcrumb — two hand-built variants with literal `"/"` separators (8371-8375, 10818-10823) and no `<nav aria-label>`.
• Progress — a raw div: `{ className: "h-full bg-yellow-400", style: { width: … } }` (9485) for the review histogram, and `` `h-full transition-all ${e !== "info" ? "bg-blue-600 w-full" : "bg-gray-200 w-0"}` `` for the checkout stepper (8984) — the latter grows from the left edge unconditionally, so it fills backwards in RTL.
• Carousel — hand-rolled with `setInterval(… 4500)` (7410), no pause on hover or focus, no `prefers-reduced-motion` check, and dot controls that are completely unlabelled buttons: `{ onClick: () => a(N), className: \`h-1.5 rounded-full transition-all duration-300 ${N === e ? "w-6 bg-white" : "w-1.5 bg-white/40 hover:bg-white/60"}\` }` — className and onClick only, no children, no `aria-label` (7474-7479).
• The only three genuinely reusable pieces in 460KB are `tt` (ProductCard), `Ya` (BundleCard) and `Sa` (a browser-chrome frame, 10528).

**Impact.** Five hand-rolled tab strips and a hand-rolled carousel means five sets of keyboard behaviour to specify and test — and currently none of them have any. The hero auto-advances every 4.5 s with no way to stop it, which is a WCAG 2.2.2 failure and actively hostile to anyone reading the Arabic copy at a slower rate. The five carousel dots are announced as "button, button, button, button, button".

**Fix.** Instruct Figma Make to use the shadcn Tabs, Select, Pagination and Breadcrumb primitives it already compiled, and to specify: tabs with roving focus and `aria-selected`; pagination with real current-page state, `aria-current="page"` and disabled prev/next at the ends; carousel with a pause/play control, pause-on-hover-and-focus, `prefers-reduced-motion` respect, and labelled dots ("Slide 1 of 3").

**Magento.** Magento ships `Magento_Theme::html/breadcrumbs.phtml`, a pager block (`Magento\Theme\Block\Html\Pager`) and `Magento_Ui` tabs — all with the accessibility scaffolding already present. Use them and style them; do not port the hand-rolled versions, which would replace working core markup with worse markup.

### `dark-mode-block-ships-with-zero-brand` — A full .dark token override ships, and it is pure shadcn greyscale — the brand is entirely absent from it

**Evidence.** make.css contains `.dark{--background:oklch(14.5% 0 0);--foreground:oklch(98.5% 0 0);--card:oklch(14.5% 0 0);--primary:oklch(98.5% 0 0);--primary-foreground:oklch(20.5% 0 0);--secondary:oklch(26.9% 0 0);--muted:oklch(26.9% 0 0);--muted-foreground:oklch(70.8% 0 0);--accent:oklch(26.9% 0 0);--accent-foreground:oklch(98.5% 0 0);--destructive:oklch(39.6% .141 25.723);--border:oklch(26.9% 0 0);--ring:oklch(43.9% 0 0);--chart-1:oklch(48.8% .243 264.376);…--sidebar:oklch(20.5% 0 0)…}` — every chroma is 0 except destructive and the charts. In dark mode `--primary` becomes white and `--accent` becomes a mid-grey; neither #0f2144 nor #f26522 survives. make.html declares `<meta name="color-scheme" content="light dark" />`.

**Impact.** If anyone ever adds `class="dark"` (or a future Magento theme toggle does), the site silently loses all brand colour and becomes a generic grey shadcn shell. Because the app hardcodes hex, the result is worse than useless: the hardcoded oranges and navies stay put while the token-driven body/border flips to dark — an unreadable hybrid.

**Fix.** Either delete the `.dark` block and the `color-scheme: light dark` meta (this design is light-only), or author a real dark palette from the brand ramp. Do not ship the shadcn default. Note for the team: the oklch values throughout `.dark` are shadcn defaults, not brand values — they must not be sampled as brand colours.

**Magento.** Magento has no built-in dark mode; the safest action is deletion. Keep `color-scheme` set to `light` only, otherwise form controls, scrollbars and `input` autofill will render dark on OS dark mode while the theme stays light.

### `date-and-number-formatting-follows-browser-not-store` — `toLocaleDateString()` / `toLocaleString()` are called with no locale and no options, so dates and thousands separators follow the visitor's browser, not the store view

**Evidence.** Five call sites, all bare:
`new Date(a.joinedDate).toLocaleDateString()` — VendorProfile 352,204 (`"Joined "` + date)
`new Date(l.date).toLocaleDateString()` — CustomerProfile 369,134 (order history `"Date:"`)
`E.toLocaleString()` — Category 302,630 (`… " products from verified sellers"`)
`o.productCount.toLocaleString()` — Search 319,256 and 322,980 (`… " items"`)
No `Intl.DateTimeFormat`, no `Intl.NumberFormat`, no Hijri handling anywhere.
Actual ICU output for the same date `2024-06-10`: `en-US` → `6/10/2024`; `en-GB` → `10/06/2024`; `ar-AE` → `10‏/6‏/2024`; `ar-SA` → `١٠‏/٦‏/٢٠٢٤`. For `1234567.89`: `en-US` → `1,234,567.89`; `ar-SA` → `١٬٢٣٤٬٥٦٧٫٨٩`.

**Impact.** Two visitors on the same Arabic store view see different date formats depending on their OS language. Worse, an `ar-SA` browser will render the order date in Arabic-Indic digits (`١٠‏/٦‏/٢٠٢٤`) directly beside a price rendered by `.toFixed(2)` in Western digits — two numbering systems in the same card. `6/10/2024` vs `10/06/2024` ambiguity on an order-history date is a support-ticket generator.

**Fix.** Replace all five with an explicit formatter bound to the store locale: `new Intl.DateTimeFormat(locale, {day:'2-digit', month:'short', year:'numeric'})` and `new Intl.NumberFormat(locale)`. Choose and document one numbering system for the ar view (Gulf e-commerce convention is Western/Latin digits with `numberingSystem:'latn'`, even in Arabic copy) and apply it to dates, prices, counts and ratings consistently.

**Magento.** Use `\Magento\Framework\Stdlib\DateTime\TimezoneInterface::formatDate()` with the store locale, never PHP `date()`. Set `general/locale/code = ar_SA` (or ar_AE) for the Arabic store view and decide whether to force `latn` digits — the Features page promises "Islamic calendar support", which would additionally require an `ar_SA@calendar=islamic` decision; nothing in the design implements it.

### `dead-shadcn-css-payload` — 634 of 1,356 compiled CSS selectors are unused — an entire shadcn/Radix component surface the Magento build must not carry

**Evidence.** make.css contains 1,356 class selectors; 722 appear in make.js and **634 do not** (47%). `@layer components` is emitted empty (`@layer components;`), so every one of these is a utility generated for components that were imported into the Figma Make project and never rendered. Identifiable by their Radix/vendor custom properties, all present in the CSS and all unused: `--radix-select-trigger-height`, `--radix-select-trigger-width`, `--radix-select-content-available-height`, `--radix-dropdown-menu-content-transform-origin`, `--radix-context-menu-content-available-height`, `--radix-hover-card-content-transform-origin`, `--radix-menubar-content-transform-origin`, `--radix-navigation-menu-viewport-height`, `--radix-navigation-menu-viewport-width`, `--radix-popover-content-transform-origin`, `--radix-tooltip-content-transform-origin`, `--radix-accordion-content-height`. Plus whole-component fingerprints: `data-[vaul-drawer-direction=left|right|top|bottom]:*` (Drawer, 16 selectors), `[&_[cmdk-group-heading]]:*`, `[&_[cmdk-input]]:h-12`, `[&_[cmdk-item]]:*` (Command palette, 11), `[&_.recharts-cartesian-axis-tick_text]:fill-muted-foreground`, `[&_.recharts-polar-grid_[stroke='#ccc']]:stroke-border`, `[&_.recharts-radial-bar-background-sector]:fill-muted` (Charts, 14 + the five unused `--chart-1..5` tokens), `[&:has(>.day-range-start)]:rounded-l-md`, `.day-range-end` (Calendar), `group-data-[collapsible=icon|offcanvas]:*`, `--sidebar-width`, `--sidebar-width-icon` (Sidebar, ~25 selectors + 7 unused sidebar tokens), `animate-caret-blink` (InputOTP), `data-[panel-group-direction=vertical]:*` (Resizable), `max-w-(--skeleton-width)` (Skeleton), `data-[state=open]:animate-accordion-down` (Accordion), plus `sr-only`, `invisible`, `fixed`, `resize-none`, `field-sizing-content` — none used.

**Impact.** Roughly half the stylesheet is dead weight, and more importantly it advertises components the design does not actually use — an implementer reading the CSS would reasonably assume Dialog, Drawer, Tooltip, Command and Sidebar are part of the system. Meanwhile the app hand-rolls the things those components exist for (see the tabs/select/pagination finding).

**Fix.** Ask Figma Make to purge the unused shadcn imports from the project before export, or to ship a build with content-scanning enabled so only used utilities compile. Then explicitly declare which shadcn primitives ARE in scope for the storefront (realistically: Select, Tabs, Dialog, Drawer, Tooltip, Skeleton, Accordion) and use them instead of hand-rolling.

**Magento.** None of this should reach `app/design/frontend/`. If the Magento theme is Hyvä (Tailwind-based) the purge is automatic once content paths are configured correctly; if it is Luma/LESS, none of these utilities are transferable at all and the CSS must be re-authored from the token layer up. Either way, do not copy make.css into the theme.

### `destructive-token-dead-red-500-used-instead` — --destructive:#c0392b has zero consumers; the real error/discount red is Tailwind red-500 #fb2c36 (Δrgb 76) at 3.81:1

**Evidence.** `--destructive:#c0392b` appears exactly once in the entire build (its own declaration in make.css) and 0 times in make.js. The utilities that would consume it — `.bg-destructive{background-color:var(--destructive)}`, `.text-destructive{color:var(--destructive)}`, `.dark\:bg-destructive\/60` — are all in the dead set. Actual reds in use: `bg-red-500` ×6 (the primary discount badge: `"absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm"` 5828), `text-red-500` ×6 (`"text-xs text-red-500 font-medium"` 'Out of Stock' 8558; `` `${e.stock > 0 ? "text-green-600" : "text-red-500"}` `` 10681), plus red-600 ×6, red-50 ×3, red-400 ×2, red-300 ×2, red-100, red-700, `hover:bg-red-50`, `hover:text-red-500` (wishlist heart). red-500 = `oklch(63.7% .237 25.331)` = **#fb2c36**, contrast on white **3.81:1**; white-on-red-500 also 3.81:1. Δrgb(#c0392b, #fb2c36) = 76 — these are visibly different reds. #c0392b itself is 5.44:1 and would pass.

**Impact.** The one red the design system declares is compliant and unused; the seven reds actually rendered are not. Out-of-stock text and every discount badge fail AA. A developer told to 'use the destructive token' would produce a colour that appears nowhere in the mockups.

**Fix.** Either set `--destructive` to the red actually intended and apply it everywhere (recommended: keep #c0392b, 5.44:1, and drop all red-3xx/4xx/5xx/6xx/7xx), or delete the token. Also split the roles: discount badges are promotional, not destructive — give them `--discount-badge-bg` rather than reusing the error red.

### `discount-badge-minus-sign-detaches` — Discount badges are built as `["-", pct, "%"]` — under RTL the minus sign detaches and renders after the number: `-25%` becomes `25%-`

**Evidence.** Seven instances of the identical pattern, e.g. product card (make.js 172,533):
```
i && r("span", { className: "absolute top-2 left-2 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full", children: [ "-", i, "%" ] })
```
Also at 174,752 / 178,099 (Layout card variants), 254,601 (Home deals grid), 311,597 (Category grid), and the bundle variants 180,208 `["-", n, "% Bundle"]` and 444,561 `["-", s, "% Bundle Deal"]`.
UAX#9 trace with base=R: `-` is bidi class ES → W6 → ON; N1 places it at the paragraph level (odd/RTL) while `25%` resolves EN at level 2; L2 reversal puts the ON after the number. Simulated output: `-25%` → `25%-`; `-25% Bundle` → `Bundle 25%-`; `-40% Bundle Deal` → `Bundle Deal 40%-`.

**Impact.** Every discount badge on every product card, on Home, Category and Bundles, renders as `25%-` in the Arabic store view — reads as a stray dash, and the minus no longer marks the value as a reduction. This is the single most repeated visual element on the site.

**Fix.** Stop concatenating JSX children for the badge. Emit one translatable phrase per locale (`en: "-{pct}%"`, `ar: "خصم ٪{pct}"` or `"-٪{pct}"`) and wrap the rendered result in `<bdi>`. Better still, drop the leading hyphen entirely and use the word form (`Save 25%` / `وفّر ٢٥٪`), which has no bidi hazard at all.

**Magento.** The same applies to Magento's price-box discount label and to any MagentoEgypt bundle-savings block — build the percentage as a single `__('Save %1%%', $pct)` phrase, not `'-' . $pct . '%'`.

### `emoji-as-icon-system` — 47 distinct emoji code points act as the icon system, including functional glyphs, and none is hidden from assistive tech

**Evidence.** `aria-hidden` occurs 0 times. Functional and structural uses: nine `<h2>` headings begin with an emoji — `"🏷️ Today's Deals"`, `"🏆 Best Selling Items"`, `"🔥 Popular Products"`, `"⭐ Top Vendors This Month"`, `"🆕 New Stores on MECommerce"`, `"🎁 Bundle Deals"`, `"🏪 Featured Stores"`. Navigation link text embeds them: `ta.filter((p) => p.emoji).slice(0, 5).map((p) => [p.emoji + " " + p.label, p.href])` in the footer, and `[p.emoji && t("span", { className: "text-sm", children: p.emoji }), p.label]` in the category bar. Pagination controls are arrow glyphs: `["←", "1", "2", "3", "→"].map((w) => t("button", { className: `w-9 h-9 rounded-lg text-sm font-medium ...`, children: w }))`. A mock bottom-nav is five bare spans: `["🏠", "🔍", "🛒", "♡", "👤"].map((f, N) => t("span", { className: "text-[11px] text-center", children: f }, N))`. Checkmarks are literal text: `children: "✓ Verified"` and `t("span", { className: "text-green-600", children: "✓" })`.

**Impact.** VoiceOver and NVDA read these aloud by their Unicode names: the heading list becomes "label, Today's Deals", "fire, Popular Products", "party popper, You qualify for free shipping". Pagination announces "leftwards arrow, button" and "rightwards arrow, button" instead of Previous/Next. The eight pastel category tiles announce "shopping trolley Grocery 2,400 plus items". First-letter heading navigation is defeated because every heading starts with an emoji, not a letter. Emoji also have no Arabic-locale equivalent and render differently per platform.

**Fix.** Replace the whole emoji icon set with the lucide SVG set already bundled (`me`, `rt`, `et`, `Ue` etc. are lucide components), each `aria-hidden="true"`. Where an emoji must stay decorative, wrap it as `<span aria-hidden="true">🔥</span>` outside the heading text node. Give the pagination arrows real labels: `aria-label="Previous page"` / `aria-label="Next page"`. Replace `"✓ Verified"` with an icon + the word "Verified".

**Magento.** Magento's icon font (`.lib-icon-font()`) plus the theme's own SVG sprite is the right target. Emoji in category labels would also flow through to `catalog_category_entity` name values if copied literally, polluting breadcrumbs, meta titles and the sitemap.

### `footer-and-nav-secondary-text-contrast` — Footer headings, tagline, country chips and payment chips all sit between 3.13:1 and 4.27:1

**Evidence.** make.js footer: `t("p", { className: "text-white/45 text-xs leading-relaxed mb-4 max-w-xs", ... })` = 4.27:1; `t("h4", { className: "text-[10px] tracking-widest uppercase text-white/35 mb-4", ... })` = 3.13:1; `t("p", { className: "text-[10px] text-white/35 uppercase tracking-widest mb-2", children: "Accepted Payments" })` = 3.13:1; country chips `className: "text-[10px] text-white/40 bg-white/5 px-2 py-0.5 rounded"` = 3.50:1 over the composited chip surface. Footer body links `"text-xs text-white/55 hover:text-white transition-colors"` = 5.69:1 and do pass.

**Impact.** Six distinct footer text layers fail 4.5:1 for normal text, all at 10–12px and several with `tracking-widest` (0.1em) which further reduces stroke density. Fails 1.4.3.

**Fix.** Standardise on three on-navy tiers only: `text-white` for headings, `text-white/70` (8.0:1) for body and links, `text-white/55` (5.69:1) as the absolute floor for de-emphasised meta. Delete /45, /40, /35, /30, /25, /20, /15 from the on-dark scale.

### `footer-legal-links-contrast` — Footer legal links and copyright render at 2.24:1 on navy — the lowest real text contrast in the build

**Evidence.** make.js footer: `r("div", { className: "border-t border-white/8 pt-6 flex flex-col md:flex-row items-center justify-between gap-3 text-[11px] text-white/25", children: [ t("p", { children: "© 2026 MECommerce. All rights reserved. Powered by Magento 2." }), ... ["Privacy", "Terms", "Cookies"].map((p) => t(C, { to: "/about", className: "hover:text-white/50 transition-colors", children: p })) ] })`. white/25 on #0f2144 = 2.24:1. Even the hover state, white/50, only reaches 4.95:1.

**Impact.** Privacy, Terms and Cookies are the links a regulator and a GDPR/PDPL-conscious Gulf shopper will look for, and they are the least legible text on the page at 11px and 2.24:1. Also fails 1.4.1: the only non-hover cue that they are links is the low-contrast colour — there is no underline.

**Fix.** Set the whole bar to `text-white/70` (8.0:1) and give the three legal links `underline`. Bump 11px to 12px minimum.

### `footer-links-collapse-to-cms` — Ten distinct footer destinations all resolve to /about, hiding the CMS and account pages the marketplace actually needs

**Evidence.** make.js:5748-5759: `{ title: "Customer", links: [["My Account", "/profile"], ["Track Order", "/about"], ["Returns", "/about"], ["Help Center", "/about"], ["WhatsApp", "/about"]] }` and `["Privacy", "Terms", "Cookies"].map((p) => t(C, { to: "/about", … }))`. No newsletter form exists anywhere in the footer or the bundle.

**Impact.** Track Order, Returns, Help Center, Privacy, Terms and Cookies are all real pages a Gulf marketplace must publish, and none are designed. There is also no Contact Us form and no newsletter signup — both are native Magento features with existing routes that the design simply omits.

**Fix.** Design the CMS page template (heading, body, sidebar/TOC) once and route these links to real pages. Add the newsletter subscribe form to the footer and a Contact Us page. Design guest order tracking as a real form.

**Magento.** Native homes exist for nearly all of these: `sales/guest/form` (track order), `contact/index` (contact form), `newsletter/subscriber/new` (footer form), and CMS pages for Privacy/Terms/Help. Also missing: 'Advanced Search' (`catalogsearch/advanced/index`), which Magento links in the footer by default.

### `gradients-untokenised-and-rtl-unsafe` — 13 gradients using 20 unique stop colours; 4 are inline-style (untouchable by CSS) and every directional one is physical, so none mirror in RTL

**Evidence.** Utility gradients: `bg-gradient-to-r` ×5, `bg-gradient-to-br` ×3, `bg-gradient-to-t` ×3. Inline-style gradients (cannot be overridden by any stylesheet): `style:{background:"linear-gradient(135deg, #080d1a 0%, #0f1b35 40%, #12103a 100%)"}` (7552), `style:{background:"linear-gradient(135deg, #0f2144 0%, #1e3a6e 100%)"}` (7797), `style:{background:"linear-gradient(135deg, #1a4731 0%, #2d7a3a 100%)"}` (7814), `style:{background:"linear-gradient(135deg, #3b1f6e 0%, #6b3fa0 100%)"}` (7831), plus `style:{background:"radial-gradient(circle, #6366f1, transparent)"}` (7557), `style:{background:"radial-gradient(circle, #f26522, transparent)"}` (7564), `style:{background:"linear-gradient(90deg, #6366f1, #a855f7)"}` (7575). Utility ones: `"bg-gradient-to-r from-[#0f2144] to-[#1a3a6a] mt-2 py-8"` (7778), `"bg-gradient-to-br from-[#0f2144] via-[#162d5a] to-[#0f2144] py-12"` (8020), `"bg-gradient-to-r from-[#fff8f5] to-white border border-[#f26522]/20"` (10884), `"bg-gradient-to-br from-blue-600 to-purple-600"` (9536 avatar, 9890 About hero), `"bg-gradient-to-r from-blue-600 to-purple-600 text-white py-16"` (10001). Hero scrims come from data: `overlay:"from-[#0f2144]/85 via-[#0f2144]/50 to-transparent"`, `"from-[#0d3320]/90 via-[#0d3320]/50 to-transparent"`, `"from-[#2a1200]/90 via-[#2a1200]/50 to-transparent"`, plus `"from-[#0f2144]/80"`, `"from-[#3a1a2e]/80"`, `"from-[#1a2a0a]/80"` (7374-7377), applied as `` `absolute inset-0 bg-gradient-to-r ${u.overlay}` `` (7440). RTL is toggled by `document.documentElement.dir = p ? "rtl" : "ltr"` (5598), but the compiled CSS is `.bg-gradient-to-r{--tw-gradient-position:to right in oklab;…}` — a physical direction. make.css contains **0** `[dir=rtl]` rules and 0 occurrences of 'rtl'.

**Impact.** (a) In Arabic the hero scrim stays anchored left while the headline flips right — the Arabic headline lands on the bright photo, unreadable, on all three hero slides plus the three promo tiles. (b) The four inline-style gradients cannot be restyled or RTL-flipped by any stylesheet at all; they would have to be edited in PHTML/JS. (c) 20 stop colours, none tokenised, including the off-brand blue-600→purple-600 pair used on the About hero, the About CTA band and the customer avatar. (d) Tailwind interpolates `in oklab`; a naive LESS port to sRGB will fade `to-transparent` through a muddy dark midpoint.

**Fix.** (1) Replace `bg-gradient-to-r` with logical direction so it mirrors (`to inline-end`, or a `[dir=rtl]` override); (2) move all inline `style={{background:…}}` gradients into classes; (3) reduce to 2–3 named gradient tokens built from brand ramp stops only, deleting blue-600→purple-600, #3b1f6e→#6b3fa0, #1a4731→#2d7a3a; (4) keep `in oklab` explicit in the exported CSS.

**Magento.** Magento generates a separate `css/styles-l-rtl.css` — put the flipped gradient there, but only after the inline styles are removed, since `style=""` beats every stylesheet. Verify on the Arabic store view with a real hero image, not a solid colour.

### `grid-ladders-inconsistent` — The same ProductCard is dropped into six different grid ladders with three different gaps

**Evidence.** Seven `tt` call sites, seven container definitions:
• `"grid grid-cols-2 md:grid-cols-4 gap-3"` — Home Today's Deals (7550), Home category rails (7776), Home Popular Products (7893)
• `"grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3"` — Category grid (8520)
• `"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"` — Product related (8353), Search results (8712), VendorProfile products (9422)
So the identical card is 2-up on mobile in four places and 1-up in three; gap is 12px in four places and 24px in three; the 768-1023px band is 4-up in three places, 3-up in one, and 2-up in three. At `md:grid-cols-4` inside `max-w-7xl` with `gap-3` each card is ~180px wide, into which the default variant must fit a 2-line `text-sm` title with `min-h-[2.5rem]`, five stars plus a review count, a `text-lg font-extrabold` price with a strike-through, and a `w-9 h-9` button.
Other containers using a hard-coded ladder: `"grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3"` (best-sellers, 7856) and `"grid grid-cols-4 md:grid-cols-8 gap-2"` (8 category tiles, 7515) — the latter is 4-up at 320px, i.e. ~70px per tile including a 3xl emoji and two lines of caption.

**Impact.** Card density changes without reason as the shopper moves between homepage, category and search, which reads as three different sites. At the 4-up tablet width the price row and add-to-cart button collide inside the ~180px card. The 8-up category tile row is unreadable on a small phone.

**Fix.** Define exactly two product grids as tokens — `product-grid` (2 / 3 / 4 / 5-up) and `product-rail` (horizontally scrollable on mobile) — with one gap value, and use them at all seven call sites. Cap the desktop density so the card's own minimum width is respected. Reduce the category tile row to 3-up at 320px or make it a scroll rail.

**Magento.** Magento controls PLP density with `<attribute name="grid_per_row">` in `catalog_category_view.xml` per breakpoint. Give the build one number per breakpoint rather than six ladders, or every listing block will need its own layout XML override.

### `h1-is-a-rotating-carousel-headline` — The homepage `<h1>` is the carousel headline and therefore changes every 4.5 seconds

**Evidence.** make.js Home component (`pc`): the only h1 is `t("h1", { className: "text-white text-2xl md:text-4xl font-bold max-w-sm mb-3 leading-tight drop-shadow-md", style: { fontFamily: "'Playfair Display', Georgia, serif" }, children: u.headline })` where `u = ot[e]` and `e` is the index mutated by the 4500ms interval. The three possible values are "Fashion from 500+ Gulf Brands", "Fresh Groceries From Local Vendors" and "Furniture & Décor for Every Home".

**Impact.** The h1 is the primary document label used by screen-reader heading navigation and by SEO. Here it is a rotating promotional string that never names the site or the page. A user who jumps to the h1 and then re-checks a moment later gets a different answer. The full heading outline for Home is h1 → h2 ×11 with the brand name appearing in none of them.

**Fix.** Make the homepage h1 a stable page identity — e.g. a visually-hidden `<h1>MECommerce — Gulf multi-vendor marketplace</h1>` — and demote the rotating carousel headline to an `<h2>` or a `<p>`.

### `hardcoded-layout-values` — Fixed pixel boxes and magic numbers that clip or misalign at other viewports and in Arabic

**Evidence.** • Hero — `style: { minHeight: 340 }` applied twice as an inline style (7429 on the grid, 7430 on the slide), with the headline capped at `"max-w-sm"` (384px) and the subtitle at `"max-w-xs"` (320px). Arabic headlines run 20-30% longer; the copy has nowhere to go inside a fixed 340px box.
• Vendors spotlight — `"w-full h-56 object-cover …"` with the entire content block as `"absolute inset-0 p-8 flex items-end gap-6"` (10131-10170): name + description + rating + CTA inside a fixed 224px overlay on a parent with `overflow-hidden`.
• Seller CTA card — `"… p-6 flex flex-col justify-between min-h-[240px]"` (10299).
• Product card title reserves — `"… min-h-[2rem]"` (compact, 5794) and `"… min-h-[2.5rem]"` (default, 5844): two lines of Latin at `text-xs`/`text-sm`. Arabic at the same size needs more leading, so the reserved box under-reserves and the price row jumps.
• Header category `<select>` — `"… min-w-[130px] appearance-none"` (5635) sized for "All Categories"; the longest option is "Electronics & Tech", and Arabic labels differ again.
• Header utility columns — three `"… min-w-[40px]"` stacks (5655, 5659, 5663) for icon + 9px caption; the Arabic captions for Account/Wishlist/Cart will not fit 40px.
• Category sidebar — `"${d ? "block" : "hidden"} md:block w-56 flex-shrink-0 space-y-4"` (8390): a fixed 224px rail, and on mobile it toggles in-flow (pushing the grid down) rather than as an overlay — there is no Drawer/Sheet in the bundle (`fixed` → 0 hits).
• Sticky offsets — `"… sticky top-24"` on both order summaries (8895, 9252) is a magic 96px against a sticky header whose height is composed of `py-3` + `py-2.5` bands and is not 96px.
• Vendor banners — sourced at `w=1200&h=400` (3:1) and rendered into `h-24` (Home/Vendors grid), `h-56` (spotlight) and `h-64` (profile), i.e. three different crops of the same asset.
• Card image ratios — `aspect-square` for products, `aspect-video` for bundles (5926): in the mixed Home rails these two never line up.

**Impact.** Arabic copy overflows or clips in the hero, the seller spotlight and the header utility stack. The mobile category filter shoves the product grid off-screen instead of overlaying it. Two sticky order summaries tuck under the sticky header. Mixed 1:1 and 16:9 cards produce ragged rows.

**Fix.** Replace every fixed height with `min-height` + intrinsic content, or with an aspect-ratio box that lets text flow below it. Replace `min-h-[2rem]`/`min-h-[2.5rem]` title reserves with `line-clamp` on a `min-height` derived from line-height so it scales with the Arabic font. Make the mobile filter a Sheet/Drawer, not an in-flow block. Derive sticky offsets from a `--header-height` custom property set by the header itself. Pick one card image ratio.

**Magento.** Magento's sticky header height differs from the design's because of the store-switcher and message blocks, so a hard-coded `top: 96px` will be wrong on day one — expose the header height as a CSS custom property. Category filters on mobile in Luma are already an off-canvas panel; matching that pattern avoids fighting core.

### `heading-hierarchy-broken` — h1 renders at five different sizes, h2 spans 18–36px, the Search page has no h1, and 18 h3s silently render larger than the h2 above them

**Evidence.** h1 sizes across pages: `text-2xl` (Cart-empty, CustomerProfile, "Product not found", "Bundle not found"), `text-3xl` (Cart, Checkout, Category, VendorProfile, BundleDetail), `text-4xl` (Bundles, "Ready to Launch Your Marketplace?"), `text-5xl` (Vendors, Features, Platform), `text-9xl` (404). h2 sizes: `text-lg`, `text-xl`, `text-2xl`, `text-3xl`, `text-4xl` — Home alone carries `"text-xl font-bold text-[#0f2144]"` and `"text-lg font-bold text-[#0f2144]"` and `"text-3xl md:text-4xl text-white font-bold"` as sibling section titles. The Search page component (`function xc()`) contains 0 `"h1"`, 5 `"h2"`, 3 `"h3"` — its top heading is `t("h2", { className: "text-xl font-semibold", children: [ l.length, " ", l.length === 1 ? "result" : "results", ' for "', e, '"' ] })`. 18 of 43 h3s carry no size class at all and fall back to the base `h3{font-size:var(--text-lg)}` = 18px, e.g. the wishlist product title `t("h3", { className: "font-medium mb-2 hover:text-blue-600 line-clamp-2", children: l.name })` and the vendor-card name `t("h3", { className: "font-semibold", children: o.name })`.

**Impact.** Heading level no longer predicts size, so structure is unreadable both to a screen-reader user and to a developer. Concretely: on Home, an h2 section title set at `text-lg` is 18px while an unsized h3 card title inside it is also 18px — the child heading is the same size as its parent. The Search results page has no h1 at all, so assistive tech and crawlers see a page whose outline starts at level 2.

**Fix.** One h1 per page, one size (plus a `md:` step). One h2 size. One h3 size. Give every heading an explicit size class — never rely on the base fallback. Add an h1 to the Search page (the "N results for X" string). Audit the 18 unsized h3s and either size them or demote them to `<p>`/`<div>` where they are not structural.

**Magento.** Map the levels onto Magento's DOM once: h1 → `.page-title .base`, h2 → `.block .block-title strong`, h3 → `.product-item-name`. Vnecoms vendor storefront templates emit their own h1 for the shop name — make sure it is not a second h1 on a page that already has one.

### `homepage-h1-rotates-every-4500ms` — The homepage's only h1 is a carousel headline that changes every 4.5 seconds

**Evidence.** `t("h1", { className: "text-white text-2xl md:text-4xl font-bold max-w-sm mb-3 leading-tight drop-shadow-md", style: { fontFamily: "'Playfair Display', Georgia, serif" }, children: u.headline })` where `const u = ot[e]` and `Te(() => { const f = setInterval(() => a((N) => (N + 1) % ot.length), 4500); return () => clearInterval(f); }, [])`. No other h1 exists in the Home component.

**Impact.** The document's single h1 is non-deterministic — it depends on when the page was sampled. SEO cannot pair a stable `<title>`/h1, screen readers re-announce the heading on every rotation if the region is live, and any automated heading-structure test is flaky. It is also 24px on mobile (`text-2xl`) with no relationship to the 48px h1 used on the marketing pages.

**Fix.** Make the h1 a static page title (visually hidden if the layout has no room for it) and demote the rotating slide headline to `<p>` or `<h2>`. Give the carousel `aria-live="off"`.

**Magento.** On Magento the homepage h1 should come from the CMS page's Content Heading, not from a slider widget block — otherwise the same instability ships to production.

### `hover-only-affordances` — Add-to-cart and wishlist on the primary product card only exist on hover — they are unreachable on touch

**Evidence.** Default ProductCard: add-to-cart is `"w-9 h-9 bg-[#f26522] hover:bg-[#d9561d] text-white rounded-lg flex items-center justify-center transition-colors shadow-sm opacity-0 group-hover:opacity-100"` (5867); wishlist is `"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-50"` (5836). The AI card repeats the pattern: `"absolute top-2.5 right-2.5 w-7 h-7 bg-black/30 backdrop-blur-sm rounded-full … opacity-0 group-hover:opacity-100 transition-all"` (7641). No `@media (hover: hover)` guard and no touch-visible fallback (the compact variant's `"w-6 h-6 bg-[#f26522] … rounded-md"` at 5808 is always visible — so the same product shows a permanent button in one rail and an invisible one in the next). Both hover-only buttons are also inside an `<a>` wrapper and defuse themselves with `onClick: (n) => n.preventDefault()`, so they currently do nothing at all.

**Impact.** On the mobile-first storefront the footer advertises, the primary quick-add and wishlist controls are invisible and effectively absent. Touch devices trigger `:hover` only after a first tap that also follows the card link, so the shopper navigates to the PDP instead of adding to cart. This removes the fastest path to purchase on the highest-traffic surface.

**Fix.** Make both buttons always visible on the card (they are 36px and 32px — small enough not to fight the image), or gate the hover reveal behind `@media (hover: hover) and (pointer: fine)` with a permanently visible variant below it. Enlarge both to a 44×44 hit area. Move them out of the `<a>` so they do not need `preventDefault`, and wire them to real handlers.

**Magento.** Magento's `.action.tocart` on a product item is normally always visible; the wishlist toggle is `.action.towishlist`. Both are form-posting controls that must sit outside the product link anchor — the current nested-anchor + preventDefault structure is not valid markup and will not survive the port.

### `images-no-lazy-no-dimensions` — 132 remote images, none lazy-loaded, none with intrinsic dimensions, none responsive

**Evidence.** `images.unsplash.com` appears 132 times. Across 36 `<img>` elements the bundle contains **0** `loading: "lazy"`, **0** `decoding`, **0** `srcSet`, **0** `sizes`, and 0 intrinsic `width`/`height` attributes on content images. Source sizes are fixed in the URL query: `w=600&h=600` ×67, `w=200&h=200` ×38, `w=400&h=300` ×11, `w=1200&h=400` ×10, `w=1100&h=580` ×3, `w=400&h=190` ×3 — so a 600×600 product image is downloaded for a card rendered at ~180px in the `md:grid-cols-4` grid, and a 1200×400 vendor banner for a strip rendered at `h-24`. The Home page alone renders the hero (1100×580), 3 side tiles, 8 category tiles, 4 deal cards, 4 AI cards, 16 rail cards, 6 best-sellers, 8 popular, 4 vendor tiles ×3 sections — all eagerly.

**Impact.** The homepage fires 60+ full-size image requests before first paint on a Gulf 4G connection. With no width/height and no aspect-ratio attribute on the `<img>` itself, every card reflows as its image resolves — a large cumulative layout shift on the most-visited page.

**Fix.** Specify `loading="lazy"` + `decoding="async"` on everything below the fold (hero and first rail stay eager, ideally with `fetchpriority="high"` on the hero), intrinsic `width`/`height` or `aspect-ratio` on every `<img>`, and a `srcset`/`sizes` set matching the grid ladder rather than one fixed source size.

**Magento.** Magento generates resized product images through `Magento\Catalog\Helper\Image` driven by `view.xml` — so the design must state the rendered size at each breakpoint for card, thumbnail, gallery and banner, and those become `view.xml` entries. Without them the build will emit one size and scale it, reproducing exactly this problem in production.

### `indigo-ai-band-offbrand` — The 'Picked For You' AI band is a self-contained indigo/violet mini-palette, with its own gradients and an indigo label at 2.27:1

**Evidence.** Band background `style:{background:"linear-gradient(135deg, #080d1a 0%, #0f1b35 40%, #12103a 100%)"}` (7552) with glow layers `style:{background:"radial-gradient(circle, #6366f1, transparent)"}` (7557) and `radial-gradient(circle, #f26522, transparent)` (7564); pill `style:{background:"linear-gradient(90deg, #6366f1, #a855f7)"}` (7575); chips `` `… ${d === N ? "border-[#6366f1] bg-[#6366f1]/20 text-white" : "border-white/10 bg-white/5 text-white/50 hover:border-white/25 …"}` `` (7608); refresh button `"… border border-white/15 text-white/60 … hover:border-[#6366f1]/60 hover:text-white …"` (7594); footnote icon `t(Ta,{className:"w-3 h-3 text-[#6366f1]/60"})` (7693) which compiles to `.text-\[\#6366f1\]\/60{color:#6366f199}` = **2.27:1** on the #12103a band. Total #6366f1 = 16 occurrences. The recommendation reasons carry a further 7 off-palette accents: `reasonColor:"#6366f1"`, `"#8b5cf6"`, `"#ec4899"`, `"#f26522"`, `"#0ea5e9"`, `"#10b981"`, `"#f59e0b"`, `"#a855f7"` (7396-7400), rendered as `style:{backgroundColor: f.color + "15"}`.

**Impact.** An entire homepage section runs on a palette (indigo/violet/pink/sky/emerald/amber) that shares nothing with the brand, and its footnote text is at 2.27:1. Extending the established note: #6366f199 is not an isolated literal — it is the visible tail of a 9-colour secondary palette used only in this band.

**Fix.** Rebuild the band on the brand navy ramp + accent (the #f26522 radial glow already there is the only on-brand element). Replace the 8 `reasonColor` hexes with 2–3 token tints. Raise the footnote to ≥4.5:1.

**Magento.** If this section is meant to be Magento's product recommendations or a Vnecoms 'related' block, it will render inside the standard page grid — a bespoke indigo palette here means one more scoped colour block in LESS forever. Fold it into the brand ramp now.

### `input-font-sizes-ios-zoom` — Four different form-control sizes; the primary search input is 14px on both breakpoints, which triggers iOS focus-zoom

**Evidence.** Base rule sets 16px: `input{font-size:var(--text-base);font-weight:var(--font-weight-normal);line-height:1.5}`. Overridden on the two search fields — desktop: `className: "flex-1 bg-white text-[#1a1a2e] text-sm px-4 py-2.5 focus:outline-none placeholder:text-gray-400 min-w-0"`; mobile: `className: "flex-1 bg-white text-sm px-3 py-2.5 focus:outline-none"`; vendor filter: `className: "w-full pl-9 pr-4 py-2.5 border border-black/12 bg-[#f6f4f1] text-sm focus:outline-none focus:border-[#0a0a0a] transition-colors"`. The Search page input is 18px: `className: "w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-lg ..."`. All checkout/profile inputs (`"w-full px-4 py-2 border rounded-lg"`) inherit 16px. The header category select is 12px: `className: "bg-white/10 text-white/80 text-xs px-3 py-2.5 border-r border-white/15 focus:outline-none cursor-pointer min-w-[130px] appearance-none"`.

**Impact.** iOS Safari auto-zooms the viewport whenever a focused control computes below 16px. That is the storefront's primary search field on both desktop and mobile breakpoints, plus the category select at 12px — so tapping search zooms the page on every iPhone, and the user has to pinch back. Four sizes (12/14/16/18px) for the same control type also means four different control heights once padding is added.

**Fix.** One input size: 16px, for every text input, select and textarea including both search fields and the category select. If 14px is wanted visually, use `font-size: 16px` with a `transform: scale()` wrapper or accept 16px — do not go below it on any focusable control.

**Magento.** Magento's `_forms.less` sets `@form-element-input__font-size: @font-size__base` (14px in Luma). Raise it to 16px for the storefront theme or iOS zoom will hit checkout fields too.

### `input-icons-and-select-carets-stay-on-physical-side` — Search icons and select carets are absolutely positioned physically, so in RTL they land on the trailing edge while the header's own search button flips to the leading edge

**Evidence.** Search page (make.js 314,984 / 315,303):
```
t(Ue, { className: "absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" }),
t("input", { placeholder: "Search for products, vendors, or categories...", className: "w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-lg …" })
```
Vendors page (402,752 / 403,049): `"w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#9e9890]"` + `"w-full pl-9 pr-4 py-2.5 …"`.
Select carets: Product (293,617) `t(Pi, { className: "w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-[#6b6560] pointer-events-none" })` with `"… px-4 py-3 pr-10 …"`; Category (308,733) `"w-3.5 h-3.5 absolute right-2.5 …"` with `pr-8`; Vendors (403,833) `"w-3.5 h-3.5 absolute right-3 …"` with `pr-8`.
By contrast the header search submit is a flex sibling (`r("button", { type: "submit", className: "bg-[#f26522] hover:bg-[#d9561d] text-white px-6 py-2.5 …" })`) and therefore DOES flip.

**Impact.** Three different search affordances end up in three different places in RTL: the header's orange search button moves to the leading (right) edge, while the Search-page and Vendors-page magnifiers stay on the trailing (left) edge with a 3rem dead gutter. The dropdown carets on Product/Category/Vendors move to the reading-start of the label instead of after it, and the `pr-*` gutter indents the Arabic option text 2–2.5rem away from the field's start edge while `px-4` leaves only 1rem on the other side — visibly lopsided fields.

**Fix.** Convert to logical: icon `left-4`→`start-4` / `left-3`→`start-3`, caret `right-3`→`end-3` / `right-2.5`→`end-2.5`; padding `pl-12 pr-4`→`ps-12 pe-4`, `pl-9 pr-4`→`ps-9 pe-4`, `pr-10`→`pe-10`, `pr-8`→`pe-8`. Then decide one convention (icon on the leading edge) and apply it to the header search too so all three match.

**Magento.** Magento's `.field .control` icon positioning and the layered-nav selects need the same `inset-inline-start/end` treatment; the Luma search icon is `right`-positioned by default and will be on the wrong side in ar.

### `inputs-no-labels-no-focus` — 28 `<label>` elements with zero `htmlFor`, seven inputs with no focus style at all, and the site search kills its own focus ring

**Evidence.** `"label"` appears 28 times; `htmlFor` appears **0** times, and no input carries an `id`. There is no `<textarea>` in the bundle (0 hits) and no `aria-describedby`.
Six unrelated input treatments:
1. Header search — `"flex-1 bg-white text-[#1a1a2e] text-sm px-4 py-2.5 focus:outline-none placeholder:text-gray-400 min-w-0"` (5646). `focus:outline-none` with **no replacement**. Same for the header category `<select>` (5635) and the mobile search input (5681) — these three are the only `focus:outline-none` in the bundle with nothing to replace it.
2. Checkout/Search — `"w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"` ×13, off-palette blue.
3. CustomerProfile — `"w-full px-4 py-2 border rounded-lg"` ×7 (9794-9852): no focus style, no border colour, no placeholder.
4. Bare selects — `"border rounded-lg px-4 py-2"` (9415, 9623), and these two are decorative: no `value`, no `onChange`, so the VendorProfile and order-history sort controls do nothing.
5. Editorial — `"w-full pl-9 pr-4 py-2.5 border border-black/12 bg-[#f6f4f1] text-sm focus:outline-none focus:border-[#0a0a0a] …"` (10184). This is the only input that uses the declared `--input-background:#f6f4f1`.
6. Native controls styled only by `accent-[#f26522]` — range (8393), radio (8415), checkbox (8437, 8441, 8449); plus `className: "rounded"` checkboxes at 9861-9875.
Declared but unused: `--input-background:#f6f4f1` (1 consumer), `--ring:#0003` (0 consumers in the JS — the app ships `ring-blue-500` instead).

**Impact.** No label is programmatically associated with its field, so screen readers announce twelve unlabelled text boxes on checkout. Clicking a label does not focus its input. Keyboard users get no visible focus on the primary site search — the most-used control on the site. Seven profile fields have no focus indication whatsoever (WCAG 2.4.7 failure). Two sort dropdowns look functional and are not.

**Fix.** One FormField component wrapping label + control + hint + error, with a generated id wired through `htmlFor`/`id`/`aria-describedby`. One input skin: token border, `--input-background` fill, and a `focus-visible` ring built from `--ring`. Never emit `focus:outline-none` without a replacement. Add the missing `<textarea>` (order notes / gift message) and a Select component with a chevron positioned with logical properties. Wire or delete the two dead sort selects.

**Magento.** Magento's `mage/validation` attaches to `<label for>`/`<input id>` pairs and injects `div.mage-error` after the field — an unlabelled markup pattern breaks core validation wiring, not just accessibility. Build the theme's form field on Magento's `.field > .label + .control` structure so core checkout, address book and newsletter forms inherit the same skin for free.

### `lang-ar-set-on-an-english-dom` — The toggle sets `document.documentElement.lang = "ar"` on a DOM whose text is 100% English

**Evidence.** `document.documentElement.lang = p ? "ar" : "en"` in the toggle handler. The shell ships `<html lang="en">` (make.html) with no `dir` attribute at all, no `og:locale`, and no `<link rel="alternate" hreflang=…>` (0 matches for `hreflang` in make.html). Every string in the tree remains an English literal.

**Impact.** Screen readers switch to an Arabic voice and apply Arabic pronunciation rules to English text — the page becomes unintelligible to assistive-technology users rather than merely untranslated. Search engines and browser translate prompts are also mis-signalled.

**Fix.** Never set `lang` ahead of the content. Set `lang`/`dir` together with the translated string set, and until translation exists, mark any element whose content stays English with `lang="en" dir="ltr"`.

**Magento.** Magento emits `<html lang="ar" dir="rtl">` from `Magento_Theme::root.phtml` based on the store-view locale, which is correct — but the theme must then guarantee the ar_SA.csv coverage is complete, otherwise untranslated fallback strings inherit `lang="ar"` and reproduce this exact defect.

### `locale-toggle-vs-store-view` — Language switch flips document.dir client-side; Magento switches locale by store view URL and full page reload

**Evidence.** make.js:5598-5599: `g = () => { const p = !i; n(p), document.documentElement.dir = p ? "rtl" : "ltr", document.documentElement.lang = p ? "ar" : "en"; }`, bound to a button showing `children: i ? "English" : "عربي"`.

**Impact.** The toggle only mirrors the layout — no text is translated, no URL changes, and no state persists across a reload. It sets the expectation of an instant in-place language flip that Magento does not provide.

**Fix.** Redesign as a store-view switcher: a link to the Arabic store view URL that reloads the page. Design the interim state (the click causes a full navigation, not an instant flip) and put the switcher where users expect a language control, alongside a country/market selector.

**Magento.** Magento resolves `dir` from the locale at render time and caches the whole page per store view in FPC — a client-side dir flip would serve LTR-cached HTML with RTL direction applied, breaking every asymmetric style. There is also no currency switcher anywhere (`grep -ci currency` = 1, in marketing prose only) despite five target markets.

### `material-pastel-tiles-and-section-bands` — 8 Material-Design pastel category tiles and 4 pale section bands introduce 16 colours from a foreign design system

**Evidence.** Category tiles (7381-7388), applied via `style:{backgroundColor: f.color}` on `"group flex flex-col items-center gap-1.5 p-3 rounded-xl hover:shadow-md transition-all duration-200"` (7520): `#e8f5e9` Grocery, `#e3f2fd` Pharmacy, `#fff3e0` Furniture, `#fce4ec` Fashion, `#ede7f6` FMCG, `#fff8e1` Kids & Toys, `#fdf5f9` Cosmetics, `#e8eaf6` Electronics — these are Material Design 50/100 tints, not Tailwind and not brand. Section bands (7389-7392), applied via `style:{backgroundColor: f.bg}` (7757) with headings `style:{color: f.accent}` (7768): `{bg:"#f0f7f1",accent:"#2d7a3a"}`, `{bg:"#fdf5f8",accent:"#9b2c5e"}`, `{bg:"#fdf5f9",accent:"#9b4b7a"}`, `{bg:"#faf7f4",accent:"#7c5a3a"}`. Tile labels are `"text-[11px] font-semibold text-gray-700"` and `"text-[9px] text-gray-400"` — gray-400 on a pastel is under 2.6:1. Note `#fdf5f9` and `#fdf5f8` differ by 1 unit in one channel — an unmistakable copy-paste duplicate.

**Impact.** Sixteen colours from a third design language sit on the homepage above the fold. Combined with the pale-green/pale-pink bands this is why the page reads as unbranded. The 11px/9px labels on pastel grounds also fail contrast, and #fdf5f9 vs #fdf5f8 will produce a visible seam between the Cosmetics tile and the Beauty band.

**Fix.** Replace all 8 tile tints with tints derived from the brand accent/primary at fixed opacities (e.g. `--accent/8`, `--primary/6`) or use a single neutral tile with the emoji/imagery carrying the category identity. Replace the 4 band backgrounds and 4 heading accents with brand tokens. Merge #fdf5f9/#fdf5f8. Raise tile label sizes to ≥12px and to a token muted colour that clears 4.5:1 on the tint.

**Magento.** These tiles are category entry points — in Magento they'd be a CMS block or a category-listing widget. Per-category colour is a merchandising feature; if it must stay, expose it as a category attribute with a constrained option list of brand tints, not 8 free hex values.

### `ml-auto-should-be-ms-auto` — Five `ml-auto` spacers push content to the wrong edge in RTL

**Evidence.** `ml-auto` appears 5×, all as layout spacers:
1. Header action cluster: `r("div", { className: "flex items-center gap-4 ml-auto md:ml-0", children: [Account, Wishlist, Cart, hamburger] })` (161,716)
2. Category nav: `"… text-[#f26522] hover:text-[#f9884a] transition-colors flex-shrink-0 border-b-2 border-transparent ml-auto", children: "🎁 Bundle Deals"` (165,110)
3. Vendors result count: `r("div", { className: "ml-auto text-sm text-[#9e9890] self-center", children: [d.length, " sellers"] })` (404,441)
4/5. Platform panel mockups: `t(re, { className: "w-4 h-4 text-[#c85c2c] ml-auto" })` (431,039) and `r("span", { className: "ml-auto text-xs text-gray-400 font-medium", … })` (449,576)
Also `mr-1` (logo divider, 159,812) and `mr-1.5` (`"w-3.5 h-3.5 inline mr-1.5 text-[#f26522]"`, 445,134).

**Impact.** `margin-left:auto` in an RTL flex row pushes toward the right, which is now the *start* — so the header account/wishlist/cart cluster collapses against the logo instead of sitting at the far end of the bar, and the "🎁 Bundle Deals" call-to-action loses its far-edge position in the category strip. The `md:ml-0` override compounds it: at ≥768px it zeroes a margin that is no longer the one doing the work.

**Fix.** `ml-auto`→`ms-auto`, `md:ml-0`→`md:ms-0`, `mr-1`→`me-1`, `mr-1.5`→`me-1.5`, `ml-0.5`/`ml-1`/`ml-1.5`/`ml-2`/`ml-3`→`ms-*` (13 further inline-gap instances).

**Magento.** n/a — pure CSS, but the same substitution applies to any `margin-left:auto` in the theme's header LESS.

### `muted-greys-and-placeholders` — Four different muted greys and all three placeholder colours fall below 3:1

**Evidence.** `text-gray-400` (#9ca3af, 36 uses) on white = 2.54:1, on the `#f5f7fa` page background = 2.37:1, on the eight pastel category tiles = 2.10–2.39:1. `text-[#9e9890]` (35 uses) on white = 2.86:1, on `#f6f4f1` = 2.60:1. `text-[#c9c4bc]` (out-of-stock variant chips) on white = 1.73:1. Placeholders: explicit `placeholder:text-gray-400` = 2.54:1 and `placeholder:text-[#9e9890]` on `#f6f4f1` = 2.60:1; the other 14 inputs have no placeholder class and fall back to Tailwind preflight `::placeholder{color:color-mix(in oklab,currentcolor 50%,transparent)}` which against `--foreground:#1a1a2e` on white computes to 3.33:1. `--muted-foreground:#6b7280` on `--input-background:#f6f4f1` = 4.40:1, also short.

**Impact.** Placeholder text in this build is the only label the checkout fields have (see checkout-labels-not-associated), so its contrast is load-bearing, not decorative. `text-gray-400` at `text-[9px]` on the pastel category tiles (2.10:1) is the worst light-surface text on the site. Fails 1.4.3.

**Fix.** Collapse the muted greys to two tokens: `#6b6560` (5.74:1 on white, already in the palette) for secondary text and `#4b5563` / gray-600 (7.56:1) for anything at or below 12px. Set placeholders explicitly to `#6b7280` on white (4.83:1) and never rely on the preflight color-mix. Raise `--muted-foreground` from `#6b7280` to `#5b6270` so it still passes on the `#f6f4f1` input surface.

### `navy-ramp-eight-undeclared-shades` — Eight dark navy/near-black shades in the header, footer and dark bands; only #0f2144 is declared

**Evidence.** `bg-[#091830]` top utility bar (5602: `"bg-[#091830] text-white/65 text-xs py-1.5 hidden md:block"`), `bg-[#0f2144]` main header (`"bg-[#0f2144] sticky top-0 z-50 shadow-lg"`) and footer (`t("footer",{className:"bg-[#0f2144] text-white mt-0"…})` 5733), `bg-[#162d5a]` category nav band (`"bg-[#162d5a] border-t border-white/8"`), plus gradient stops `to-[#1a3a6a]` (7778), `via-[#162d5a]` (8020), `#1e3a6e` (7797), and the AI band `style:{background:"linear-gradient(135deg, #080d1a 0%, #0f1b35 40%, #12103a 100%)"}` (7552) — #080d1a, #0f1b35, #12103a. Only `--primary:#0f2144` is declared; #091830, #162d5a, #1a3a6a, #1e3a6e, #080d1a, #0f1b35, #12103a are all undeclared literals. Note #12103a is a violet-navy (blue-purple), not the brand navy.

**Impact.** The 190px header is three different navies stacked, none of them derived from the brand value, so any brand change requires hand-editing seven more hexes. The gradient endpoints also drift toward purple (#12103a) and slate (#0f1b35), pulling the dark bands off-hue.

**Fix.** Derive the ramp from `--primary`: `--primary-900` (#091830), `--primary` (#0f2144), `--primary-700` (#162d5a), `--primary-600` (#1a3a6a) — four steps, one hue, all computed from one source. Delete #1e3a6e, #080d1a, #0f1b35, #12103a and rebuild the AI band from the same ramp.

**Magento.** In LESS this is `@primary__color` plus `darken()/lighten()` derivations, so the header/footer stay in sync automatically. Worth insisting on: the header is the one component every Magento page renders.

### `neutral-surface-ramps-collide` — Three near-identical surface greys and two full neutral ramps (cool vs warm) coexist; #f5f7fa and gray-50 are visually the same colour under two names

**Evidence.** Surfaces: `bg-[#f5f7fa]` ×6 — including the app shell itself, `t("div",{className:"min-h-screen bg-[#f5f7fa]",style:{fontFamily:"'DM Sans', system-ui, sans-serif"}…})` (5601) — vs `bg-gray-50` ×39 (resolves to **#f9fafb**, Δrgb from #f5f7fa = **5**, i.e. indistinguishable) vs `bg-[#f6f4f1]` ×20 (warm, Δrgb from #f5f7fa = 10). Two full ramps: COOL = #f5f7fa (`--muted` AND `--secondary`, identical values) / #6b7280 (`--muted-foreground`, unused) / gray-50 #f9fafb / gray-100 #f3f4f6 / gray-200 #e5e7eb / gray-300 #d1d5dc / gray-400 #99a1af / gray-500 #6a7282 / gray-600 #4a5565 — used by Layout, Home, Category, Cart, Checkout, Search, VendorProfile, CustomerProfile, About, Bundles. WARM = #f6f4f1 (`--input-background`, `--sidebar`) / #ede9e3 (`--sidebar-accent`, 1 occurrence, never used in JS) / #e0ddd8 / #d0cdc8 / #c9c4bc (`--switch-background`) / #9e9890 / #6b6560 — used by Product, Vendors, Features, Platform. Warm-ramp step deltas are also uneven: #c9c4bc↔#d0cdc8 = 17, #d0cdc8↔#e0ddd8 = 28, #e0ddd8↔#ede9e3 = 21.

**Impact.** Three page-background greys that a user cannot tell apart but a developer must maintain separately, plus two complete neutral scales with no rule for which applies where. Section bands on Home will look subtly mismatched against cards (#f9fafb card image wells inside a #f5f7fa page). The warm ramp is also the tail of the abandoned System B.

**Fix.** Pick one neutral family. Collapse #f5f7fa + gray-50 + gray-100 into a single `--muted`/`--surface` value; delete the warm ramp (#f6f4f1, #ede9e3, #e0ddd8, #d0cdc8, #c9c4bc, #9e9890, #6b6560) along with System B; publish a 5-step neutral scale with even perceptual steps and use only those.

**Magento.** Magento/Luma already ships `@color-gray*` with ~9 steps; give the theme a 5-step override and forbid raw greys in templates. `--switch-background:#c9c4bc` and `--sidebar*` should be deleted outright — this design has no switch and no sidebar (see orphan-tokens finding).

### `nineteen-price-treatments` — Eleven current-price and eight was-price typographic treatments for the same datum

**Evidence.** Current price: `"text-sm font-bold text-[#0f2144]"`, `"text-base font-extrabold text-[#0f2144]"`, `"text-lg font-extrabold text-[#0f2144]"`, `"text-xl font-extrabold text-[#0f2144]"`, `"text-sm font-extrabold text-white"`, `"text-sm font-extrabold text-[#f26522]"`, `"text-4xl font-extrabold text-[#0f2144]"`, `"text-lg font-bold"`, `"font-semibold"`, `"font-medium"`, and the Playfair PDP `"text-4xl text-[#0a0a0a]"` + `fontWeight: 700`. Was-price: `"text-[9px] text-gray-400 line-through ml-1"`, `"text-[10px] text-gray-400 line-through"`, `"text-[10px] text-white/30 line-through ml-1.5"`, `"text-xs text-gray-400 line-through"`, `"text-sm text-gray-400 line-through"`, `"text-sm text-gray-500 line-through"`, `"text-lg text-[#9e9890] line-through"`, `"text-xl text-gray-400 line-through"`.

**Impact.** Nineteen shapes for one number spanning 9px → 36px, three families of grey, one serif and one sans, two weights that render identically (see the DM Sans finding). A customer cannot learn the price shape, and a developer cannot build one price block — every surface becomes bespoke.

**Fix.** Define exactly three current-price sizes — card 16px/600, PDP 32px/600, cart-line 14px/600 — one was-price rule (0.875× the current price, never below 12px, one grey) and one saving badge. All DM Sans, all `font-variant-numeric: tabular-nums`. Replace all 19 call sites with the component.

**Magento.** Maps cleanly onto `.price-box .price`, `.old-price .price`, `.special-price` and `.price-container` size modifiers — three rules in `_typography.less` instead of nineteen inline treatments.

### `no-arabic-webfont` — Neither loaded font has Arabic glyphs, and 45 inline `fontFamily` overrides make the fallback unfixable from a stylesheet

**Evidence.** make.css line 1: `@import"https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap"` — no `&subset=arabic`, and neither family ships Arabic coverage.
make.js contains 45 inline style overrides: `fontFamily: "'Playfair Display', Georgia, serif"` ×40 and `fontFamily: "'DM Sans', system-ui, sans-serif"` ×5. Compiled CSS confirms `font-family:Playfair Display,Georgia,serif` ×3 and `font-family:DM Sans,system-ui,sans-serif` ×1.
The Playfair import also requests italics (`ital,wght@1,400;1,600`), and `italic` is used 3× in the markup.

**Impact.** Every Arabic heading falls through Playfair → Georgia (no Arabic) → generic serif, i.e. the OS default. Headings and body will render in two visually unrelated typefaces with mismatched x-height and baseline, and the display/body hierarchy the design establishes in English is lost entirely in Arabic. Because the family is set via the React `style` prop, no store-view stylesheet can override it without `!important` on 45 elements. Italic is not a valid style for Arabic — synthesised oblique on Arabic is a typographic error.

**Fix.** Pick and load an Arabic display + text pair (e.g. Noto Kufi Arabic / Almarai / IBM Plex Sans Arabic; Playfair's closest Arabic counterpart for display is a Naskh face such as Noto Naskh Arabic). Remove all 45 inline `fontFamily` props and move the stacks to two CSS custom properties (`--font-display`, `--font-body`) so the Arabic store view can swap them in one place. Include the Arabic families in the same `@import`. Drop `italic` from the Arabic theme.

**Magento.** Declare the font stacks in the theme's `_variables.less` (or `_typography.less`) and override them in an `ar` store-view LESS file. Self-host the fonts under `web/fonts/` rather than Google's CDN — Gulf CDN latency plus GDPR/PDPL concerns.

### `no-autocomplete-or-validation-semantics` — Checkout has no `autocomplete`, no `required`, no `aria-invalid`, no `aria-describedby` and no error messaging

**Evidence.** All zero across make.js: `autoComplete` = 0, `autocomplete` = 0, `required` = 0, `aria-invalid` = 0, `aria-describedby` = 0, `role=` = 0, `inputMode` = 0. The 12 checkout fields are all `type: "text"` (including Phone, which is `type: "tel"`, and Email, `type: "email"`) with only a `placeholder`. The one error affordance in the build is a colour swap on the PDP variant select: `` `${y ? "border-red-300 text-red-500 focus:border-red-400" : ...}` ``, where border-red-300 (#fca5a5) on white = 1.90:1 and text-red-500 = 3.76:1.

**Impact.** No `autocomplete` fails WCAG 1.3.5 Identify Input Purpose (AA) and forces every user — especially motor-impaired users relying on browser autofill — to hand-type name, address and card details. No `aria-invalid` / `aria-describedby` / `role="alert"` means validation failures, when the real implementation adds them, will be invisible to assistive tech (3.3.1). The one existing error state is signalled by red border + red text only, and the border is at 1.90:1 — fails 1.4.1 and 1.4.11.

**Fix.** Add the standard autocomplete tokens: `given-name`, `family-name`, `email`, `tel`, `street-address`, `address-level2`, `address-level1`, `postal-code`, `cc-number`, `cc-name`, `cc-exp`, `cc-csc`; add `inputMode="numeric"` on ZIP/CVV. Add `required` plus `aria-required`. Every error message gets an `id`, is referenced by `aria-describedby`, the field gets `aria-invalid="true"`, and the error is prefixed by a text/icon cue, not colour alone. Darken the error border to `#c0392b` (5.44:1, already the `--destructive` token).

**Magento.** Magento's `Magento_Checkout` shipping/payment KO templates already emit autocomplete tokens and `aria-describedby` on `.field-error`. Ensure the theme override only restyles those nodes and does not replace the markup.

### `no-bidi-isolation-anywhere` — Zero bidi isolation in the entire bundle — no `<bdi>`, no `dir` attribute on any element, no LRM/RLM/FSI marks — so order IDs, card numbers, phone numbers and vendor names will corrupt inside Arabic sentences

**Evidence.** Counts across make.js + make.css + make.html: `<bdi` = 0, `"bdi"` = 0, `bdo` = 0, `dir=` = 0 (in JS; make.html has only `<html lang="en">`), `unicode-bidi` = 0, U+200E LRM = 0, U+200F RLM = 0, U+2066–2069 (LRI/RLI/FSI/PDI) = 0, U+061C ALM = 0. The only `dir` write in the codebase is the imperative `document.documentElement.dir = p ? "rtl" : "ltr"`.
Unprotected LTR payloads currently in the design: `id: "ORD-2024-001"` rendered as `t("span",{className:"font-semibold ml-2", children: l.id})` next to `children: "Order #"`; card placeholder `"1234 5678 9012 3456"` (reorders to `3456 9012 5678 1234` under RTL); phone `"+1 (555) 000-0000"` (reorders to `000-0000 (555) 1+`); vendor names like `"Qatar Dairy Co."` and `"TechGear Pro"`; spec strings like `"Intel Core i9-14900KF, NVIDIA RTX 4080 Super, 32GB DDR5 RAM, 1TB NVMe SSD"`.

**Impact.** Once real Arabic copy surrounds these values the trailing/leading neutral characters (`.`, `#`, `+`, `-`, `(`, `)`) detach and migrate to the wrong end of the run, and digit groups transpose. Concretely: a customer reading an order number aloud to support gets the wrong string; a card number typed into the RTL-inherited `<input>` shows its four groups in reverse visual order, which reads as a validation failure to the shopper.

**Fix.** Wrap every machine-readable or user-supplied LTR value in `<bdi>` (or `<span dir="ltr">`): order IDs, SKUs, tracking numbers, phone numbers, emails, URLs, vendor/brand names, model/spec strings. Add `dir="ltr" inputmode="numeric"` explicitly to the card-number, CVV, expiry and phone inputs, and `dir="auto"` to free-text inputs (search, name, address).

**Magento.** Apply the same in .phtml: `<bdi><?= $block->escapeHtml($order->getIncrementId()) ?></bdi>`. Vnecoms vendor names come from user input and must be `dir="auto"` wrapped in `<bdi>` — a vendor with an Arabic store name next to a Latin one in a listing will otherwise reorder the surrounding punctuation.

### `no-button-component` — 74 raw `<button>` elements: 7 primary fills, 3 different accent-hover hexes, 10 icon-button sizes, 3 quantity steppers

**Evidence.** No button component and no variant machinery — `cva`/`class-variance-authority` → 0 hits, `data-slot` → 0 hits. 74 `"button"` element literals plus ~15 `<Link>` elements styled as buttons.
Primary fills in use: `bg-[#f26522] hover:bg-[#d9561d]` (5649, 5808, 5867, 5983, 10937); `bg-[#0f2144]` (8488, 8568, 10983); `bg-[#0a0a0a] text-white hover:bg-[#c85c2c]` (8282); `bg-[#c85c2c] hover:bg-[#b54e24]` (10299); `bg-blue-600 hover:bg-blue-700` (13 sites); `bg-green-600 hover:bg-green-700` (9238); `bg-white text-blue-600 hover:bg-blue-50` (10005).
The accent hover token is three different hexes: `#d9561d` (5 uses), `#c85c2c` (as a hover on 8282), `#b54e24` (10299).
Text/vertical rhythm: `py-1.5`, `py-2`, `py-2.5`, `py-3`, `py-3.5`, `py-4` crossed with `px-3`, `px-4`, `px-6`, `px-8` — plus radius `rounded-md`, `rounded-lg`, `rounded-xl`, `rounded-full` and *none* (the editorial pages use square buttons: `"border border-black/15 p-3.5 hover:border-[#0a0a0a]"`, 8293).
Icon buttons: `w-6 h-6` (5808), `w-7 h-7` (7641, 7666), `w-8 h-8` (5836), `w-9 h-9` (5867, 7485, 8568), `w-10 h-10` (8262), `p-2` (8871), `p-3.5` (8293), `w-10 h-11` (10920). Six of these are below the 44×44 touch target minimum.
Three quantity steppers: PDP `"w-10 h-10 … border-r border-black/15 …"` with text `"−"`/`"+"` (8262-8277); Cart `"p-2 hover:bg-gray-50"` with lucide Minus/Plus icons and `"w-12 text-center"` value (8867-8886); Bundle `"w-10 h-11 … text-lg"` with text `"−"`/`"+"` inside `"border border-gray-200 rounded-lg overflow-hidden"` (10917-10932).
No `:focus`/`:focus-visible` style on any button in the bundle (`focus-visible` → 0 hits).

**Impact.** Every implementer will guess. Six icon buttons under 44px fail touch-target guidance on the mobile-first storefront the footer advertises. Three quantity steppers means the same interaction is re-learned on PDP, cart and bundle page. The three accent-hover hexes mean the orange "pressed" feel differs by page.

**Fix.** One Button: `variant: 'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive'`, `size: 'sm' | 'md' | 'lg' | 'icon'` (icon = 44×44 minimum), `loading`, `disabled`, `iconStart`/`iconEnd`, `fullWidth`. One accent-hover token. One QuantityStepper component with `min`, `max`, `disabled` and a disabled-at-minimum state. Add an explicit `focus-visible` ring to the Button spec.

**Magento.** Magento core already emits `button.action.primary`, `.action.secondary`, `.action.tocart`, and the qty stepper in `Magento_Checkout::cart/item/default.phtml`. Style those class hooks once. Seven bespoke button treatments would each need a separate override and would still leave core buttons (checkout agreements, address book, wishlist) unstyled.

### `no-live-regions` — `aria-live` appears zero times, so every dynamic state change on the site is silent

**Evidence.** `grep -c 'aria-live' make.js` = 0; `role=` = 0. Silent updates include: cart quantity, where the value is a plain `t("span", { className: "w-12 text-center font-medium", children: v })` mutated by the unnamed +/− buttons; the Category filter result count `r("span", { className: "text-sm text-gray-500", children: [t("span",{className:"font-semibold text-[#0f2144]",children:R.length}), " results"] })`; the AI Refresh button which swaps the whole recommendation grid; the free-shipping status `t("p", { className: "text-xs text-green-600", children: "🎉 You qualify for free shipping!" })`; and the deals countdown driven by `setInterval(..., 1e3)`.

**Impact.** A screen-reader user presses "+" on a cart line and receives no confirmation that anything changed — quantity, line total and order total all update silently. Filtering a category gives no indication that the result set changed. Fails WCAG 4.1.3 Status Messages.

**Fix.** Add a single visually-hidden `<div aria-live="polite" aria-atomic="true">` per page and push status strings into it: "Quantity updated to 3, line total AED 897", "24 results", "Recommendations refreshed". Do NOT put the 1-second countdown in a live region — instead give it `aria-hidden="true"` and expose the deadline once as static text.

### `no-reduced-motion-guard` — `prefers-reduced-motion` appears zero times, while three infinite animations and 12 hover-scale transforms run unconditionally

**Evidence.** `grep -c prefers-reduced-motion make.css` = 0; `grep -c 'prefers-reduced-motion\|motion-reduce' make.js` = 0. Infinite animations present: `animate-ping` on the AI Engine badge dot (`"animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"`), `animate-spin` on the Refresh icon, `animate-pulse`, plus the compiled `@keyframes caret-blink` running `animation:1.25s ease-out infinite caret-blink`. Transform/opacity motion: 12 × `group-hover:scale-105`, 1 × `group-hover:scale-110`, 1 × `hover:scale-105` (on the hero CTA), 26 × `transition-all`, 14 × `transition-transform` including `transition-transform duration-500` on every product image.

**Impact.** Fails WCAG 2.3.3 Animation from Interactions. The `animate-ping` badge is a continuously pulsing element with no off switch. On a product grid, 12 simultaneous 500ms image scale-ups on hover/scroll is a vestibular trigger. Users with vestibular disorders, migraine or ADHD have no escape hatch.

**Fix.** Add one global block to the theme stylesheet: `@media (prefers-reduced-motion: reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}}`. Additionally, gate the carousel `setInterval` on `window.matchMedia('(prefers-reduced-motion: reduce)').matches` so the auto-advance does not start at all for those users.

### `no-responsive-type-above-36px` — Only 6 of 475 size declarations are responsive; 48px and 128px headings are unguarded on mobile

**Evidence.** The entire bundle contains `md:text-4xl` ×4, `md:text-3xl` ×1, `md:text-5xl` ×1 — 6 responsive size utilities out of 475. Unguarded: `t("h1", { className: "text-5xl text-[#0a0a0a] mb-4", style: { fontFamily: "'Playfair Display', Georgia, serif", fontWeight: 800, lineHeight: 1.1, letterSpacing: "-0.02em" }, ... })` on Vendors, and the same pattern with `lineHeight: 1.08` on Features and Platform; `t("h2", { className: "text-4xl text-[#0a0a0a] mb-5", style: { ..., fontWeight: 700, lineHeight: 1.15 } })` ×2; `t("h2", { className: "text-3xl text-[#0a0a0a]", ... children: "Related Products" })` on PDP; `t("h1", { className: "text-9xl font-bold text-blue-600 mb-4", children: "404" })`.

**Impact.** 48px Playfair 800 at line-height 1.1 with -0.02em tracking on a 360px viewport wraps to 2–3 words per line and the tight leading makes the lines collide; the 128px 404 overflows. Only the Home hero and the PDP title were given mobile steps — the three marketing landing pages, the PDP 'Related Products' heading and the 404 were not. The design is effectively desktop-only above 36px.

**Fix.** Every size ≥24px needs a mobile step (`text-3xl md:text-5xl`) or a `clamp()` (`clamp(1.75rem, 5vw, 3rem)`). Raise line-height on the mobile step — 1.08 is only survivable at 48px+, never at 28px.

**Magento.** Bake the clamps into the LESS type mixins so Magento's own `.page-title` inherits them rather than needing per-page media queries.

### `on-dark-white-alpha-ramp` — A 14-step ad-hoc white-alpha text ramp on dark bands, with the four most-used steps failing AA — including the footer copyright at 2.25:1

**Evidence.** Distinct `text-white/N` steps in make.js: /15, /20, /25, /30, /35, /40, /45, /50, /55, /60, /65, /70, /75, /80 — 14 values, none tokenised. Counts: /65 ×10, /40 ×7, /55 ×6, /50 ×6, /80 ×5, /60 ×4, /35 ×4, /70 ×3, /45 ×2, /30 ×2, /25 ×1, /20 ×1, /15 ×1. Contrast measured against the actual footer/header background #0f2144: /25 = **2.25:1**, /30 = 2.66:1, /35 = **3.14:1**, /40 = **3.68:1**, /45 = **4.28:1**, /50 = 4.95:1 (pass), /55 = 5.69:1 (pass). Failing instances: `"border-t border-white/8 pt-6 flex flex-col md:flex-row items-center justify-between gap-3 text-[11px] text-white/25"` containing `"© 2026 MECommerce. All rights reserved. Powered by Magento 2."` (5757) = 2.25:1 at 11px; `"text-[10px] tracking-widest uppercase text-white/35 mb-4"` on every footer column heading (5753) = 3.14:1; `"text-white/45 text-xs leading-relaxed mb-4 max-w-xs"` brand tagline (5740) = 4.28:1; `"text-white/40 text-xs"` AI-section subtitle (7587); `"text-[10px] text-white/30 line-through ml-1.5"` was-price on the Home dark card (7660) = 2.66:1. Also 8 `border-white/N` steps (5,8,10,15,20,25,30,40) and 8 `bg-white/N` steps (5,6,10,12,20,40,60,90).

**Impact.** Footer legal text, all footer section headings, the payment-methods strip and the dark-card was-prices are below AA. In total 32 distinct black/white alpha steps are in play across 8 utility families with no rule — a maintainer has no way to know whether /35 or /40 is 'correct'.

**Fix.** Define three on-dark text tokens — `--on-dark` (100%), `--on-dark-muted` (≥60% ⇒ 6.5:1), `--on-dark-subtle` (≥50% ⇒ 4.95:1) — and one border token `--on-dark-border` (~10%). Replace all 14 text steps and 8 border steps. Floor every on-dark text at /50.

**Magento.** Footer and header are `Magento_Theme` blocks rendered on every page, so this is a site-wide AA failure, not a one-page one. Encode as `@on-dark__color__muted` etc. in `_theme.less`; also re-check after the Arabic font fallback lands, since lighter fallback strokes worsen effective contrast.

### `order-history-vendor-split` — Order history uses non-Magento statuses and models one order per checkout, ignoring the Vnecoms per-vendor split

**Evidence.** make.js:9513-9531: `status: "Delivered"`, `status: "In Transit"`, `status: "Processing"`, each order carrying one `total` and one status. Buttons: `children: "Track Package"` (singular), `children: "Buy Again"`. Meanwhile the cart explicitly groups by vendor (`d = e.reduce((m, u) => { const g = u.product.vendorId; ...`) and promises `"Orders from multiple vendors in one checkout"`.

**Impact.** The design contradicts itself: the cart knows about multiple vendors, order history does not. A checkout spanning 3 vendors produces 3 shipments with 3 tracking numbers and possibly 3 different statuses, which a single status pill and a single 'Track Package' button cannot represent.

**Fix.** Redesign the order card as a parent order containing N vendor blocks, each with its own status, items, shipping method and tracking link. Map the status vocabulary to real Magento states.

**Magento.** 'Delivered' and 'In Transit' are not Magento order states — core has pending/processing/complete/closed/canceled/holded, and delivery progress lives on shipment tracking, not the order. Either add custom `sales_order_status` entries mapped to those labels, or derive the label from shipment data. Decide before the Odoo/fulfilment integration hardens.

### `page-route-map-gaps` — Route map: 4 of 14 designed pages have no native Magento route; 3 designed routes collide with Magento URL conventions

**Evidence.** Router block (make.js:11042-11063): `{ index: !0, Component: pc }, { path: "product/:id" }, { path: "category/:slug" }, { path: "search" }, { path: "cart" }, { path: "checkout" }, { path: "vendors" }, { path: "bundles" }, { path: "bundles/:id" }, { path: "features" }, { path: "platform" }, { path: "vendor/:id" }, { path: "profile" }, { path: "about" }, { path: "*" }`

**Impact.** Implementation has no agreed target for /bundles, /features, /platform, and /profile, and the /product/:id and /category/:slug shapes are numeric-ID / bare-slug URLs that Magento's url_rewrite layer does not produce, so every internal link in the design has to be rewritten during the port or SEO breaks.

**Fix.** Bind each page to an explicit Magento target before build: Home -> cms_index_index (CMS home page); Category -> catalog_category_view / catalog_category_view_type_layered at /<url_key>.html; Product -> catalog_product_view (+ catalog_product_view_type_new_bundle) at /<url_key>.html; Cart -> checkout_cart_index; Checkout -> checkout_index_index (Amasty OSC handle); Search -> catalogsearch_result_index at /catalogsearch/result/?q=; Vendors -> Vnecoms seller directory /sellerlist; VendorProfile -> Vnecoms microsite /shop/<vendor_key>; CustomerProfile -> customer_account_index + sales_order_history + wishlist_index_index + customer_address_index + customer_account_edit; About/Features/Platform -> cms_page_view; NotFound -> cms_noroute_index. Bundles has no native route: make it a real category ('Bundle Deals') containing new_bundle products so it gets catalog_category_view for free, rather than a custom controller.

**Magento.** Only 10 of 14 pages map 1:1. Bundles, Features, Platform are the ones to decide on; Features/Platform are pure marketing and belong in CMS pages, which also lets Marketing edit them without a deploy.

### `pagination-and-back-arrows-hardcoded-glyphs` — Pagination and the bundle back-link use literal `←`/`→` characters, which Unicode does not bidi-mirror

**Evidence.** Category pagination (make.js 313,808):
```
R.length > 0 && t("div", { className: "mt-8 flex justify-center gap-1.5", children: ["←", "1", "2", "3", "→"].map((w) => t("button", …)) })
```
Bundle detail (make.js 442,873): `t(C, { to: "/bundles", className: "text-[#f26522] hover:underline text-sm", children: "← Back to Bundles" })`.
`unicodedata.mirrored('←')` = 0 and `unicodedata.mirrored('→')` = 0 — U+2190/U+2192 have Bidi_Class=ON but Bidi_Mirrored=No, so the UBA will never flip them. Simulation: `"← 1 2 3 →"` → `→ 3 2 1 ←`; `"← Back to Bundles"` → `Back to Bundles ←`.

**Impact.** Pagination is doubly wrong in RTL: the flex row reverses the button positions (so the "→ next" button lands on the left) while the glyph keeps pointing right. The result is a control on the leading edge whose arrow points away from the direction it navigates. The bundle back-link renders with the arrow after the text, still pointing left, which in Arabic reads as "forward".

**Fix.** Replace the literal `←`/`→` characters with the existing lucide `ChevronLeft`/`ChevronRight` components (already bundled as `io`/`no`) and swap them on direction, or apply `rtl:-scale-x-100`. Also replace the hard-coded `["←","1","2","3","→"]` array with real Prev/Next semantics and `aria-label`s so the affordance survives translation.

**Magento.** Magento's default pager uses `<span class="action next">` with a CSS `::before` icon-font glyph; that glyph must be swapped (or the element `transform: scaleX(-1)`) in the ar store view LESS.

### `pdp-no-other-sellers` — PDP has no 'Other Sellers' offer list — the defining marketplace feature is missing

**Evidence.** `grep -ciE 'other seller|more sellers|Sold by' make.js` = 0. The PDP vendor block (make.js:8296-8330) renders exactly one vendor: `const u = at.find((E) => E.id === a.vendorId)` and a single `children: "Visit Store"` button.

**Impact.** Every product is permanently bound to one seller with one price. There is no price comparison, no 'N offers from AED X', no per-seller shipping/ETA/rating comparison, and no buy-box concept — so competing vendors listing the same product have no way to surface.

**Fix.** Add an offers block under the buy box: seller name + verified badge + rating + price + delivery estimate + Add to Cart, one row per competing offer, with the winning offer pre-selected in the buy box.

**Magento.** Magento has no native multi-offer product concept and Vnecoms lists each vendor's item as a separate product entity. Delivering this needs a grouping key (shared SKU/GTIN attribute) plus a custom block that queries sibling products — non-trivial, so decide now whether it is in scope rather than discovering it at build time.

### `pdp-no-review-block` — PDP shows a review count but has no review list and no review-submission form

**Evidence.** make.js:8158-8163 renders `"(", a.reviews, " reviews)"` next to the stars. Scanning the whole PDP component (make.js:8081-8364) for a reviews section returns nothing — the page is gallery, buy box, trust badges, vendor card, then `children: "Related Products"`. `grep -c 'Write a review' = 0` within the PDP range.

**Impact.** Lof product reviews has nowhere to render. Customers cannot read or write reviews on the product page — the only 'Write Review' button in the whole design is in order history (make.js:9686) and it points at nothing.

**Fix.** Add a PDP tab/section set: Description, More Information (attribute table), Reviews (rating summary + distribution bars + paginated list + 'Write a Review' form with star input, nickname, summary, body). Reuse the rating-distribution bars already drawn on the vendor page (make.js:9478-9494) for consistency.

**Magento.** Magento's default PDP is a tab set (`product.info.details`) — the design removed it entirely, so Description, More Information, Reviews and any Lof block all lose their host. This is a removal of native structure, not an addition.

### `pdp-variant-model-unbuildable` — PDP option model mixes configurable-product stock with custom-option price deltas, and renders two controls for the same attribute

**Evidence.** Product data (make.js:6069-6087): `{ value: "rose-gold", label: "Rose Gold", inStock: !1 }` and `{ value: "bluetooth-usbc", label: "Bluetooth + USB-C", inStock: !0, priceModifier: 20 }`. The PDP renders both a `<select>` (`children: ["Select ", E.name, "…"]`, option labels appended with `` j.priceModifier ? ` (+$${j.priceModifier})` : "" ``) AND a swatch row `E.options.map((j) => t("button", { onClick: () => j.inStock && k(E.name, j.value) ...}))` for the same option group.

**Impact.** No single Magento product type produces this. Configurable products give per-option stock but derive price from the child product (no '+$20' label). Custom options give a '+$20' price delta but have no stock at all. Building it as drawn means a custom price/stock renderer on every PDP.

**Fix.** Pick one: (a) configurable — drop the '(+$X)' suffixes and let the price block update on selection, keep the disabled/struck-through OOS state; or (b) custom options — drop per-option stock and the OOS strike-through. Also delete either the select or the swatch row; showing both for one attribute is a duplicate control.

**Magento.** Option (a) is the native path and matches the swatch UI already drawn; Magento's swatch renderer disables unavailable combinations for free. The '(+$X)' label is the only thing that has to go.

### `plp-card-addtocart` — Product-card add-to-cart and wishlist are hover-only icon buttons with no handler, and no 'Select Options' state for products that have variants

**Evidence.** make.js:5863-5871: `t("button", { className: "w-9 h-9 bg-[#f26522] hover:bg-[#d9561d] text-white rounded-lg flex items-center justify-center transition-colors shadow-sm opacity-0 group-hover:opacity-100", onClick: (n) => n.preventDefault(), children: t(rt, …) })`. Same `opacity-0 group-hover:opacity-100` and `onClick: (n) => n.preventDefault()` on the wishlist heart.

**Impact.** `opacity-0 group-hover:` means both actions are invisible and unreachable on every touch device — i.e. the majority of a Gulf marketplace's traffic. Separately, the design's own products carry `variants` (Color, Connectivity, Case Size), and a one-click add cannot work for those.

**Fix.** Make the actions permanently visible on touch breakpoints, and add a 'Select Options' card state for products with variants that navigates to the PDP instead of adding.

**Magento.** Magento core already refuses to add configurable and bundle products from the list — it renders the button but routes to the PDP. Without the alternate label the card promises an action the platform will not perform. Also note the card leads with `e.vendorName`, which `Magento\Catalog\Block\Product\ListProduct` has no field for: it needs a ViewModel resolving vendor_id -> name with the whole page's vendors preloaded, or the 12-card grid triggers N+1 queries.

### `plp-layered-nav-non-native` — Category sidebar filters are four features Magento's layered navigation does not have, plus one that is wired to nothing

**Evidence.** make.js:8390-8455: `"Price Range"` as `input type="range" min="0" max="2000"` showing `["Up to $", c]`; `"Minimum Rating"` as `[4.5, 4, 3.5, 0].map(...)` radios; `"Availability"` with `"In Stock Only"` and `"On Sale"` checkboxes; and `"Vendors"` rendering `t("input", { type: "checkbox", className: "accent-[#f26522]" })` with no `onChange`, no `checked`, and no reference in the filter chain `S = S.filter(...)`.

**Impact.** Every filter in the sidebar except price is either non-native or dead. The vendor filter — the one filter a marketplace most needs — is decoration that does nothing when clicked, which will read as a bug the moment it ships.

**Fix.** Replace the continuous slider with Magento's price bucket links (or budget an AJAX layered-nav module for a real two-handle slider). Make the vendor checkboxes real. Decide explicitly whether rating / in-stock / on-sale are worth the indexer work, or cut them.

**Magento.** Rating is the expensive one: it lives in `review_entity_summary`, not as a catalog attribute, so it is not in `catalog_product_index_eav` or the OpenSearch index. It needs a custom `rating_summary` int attribute, an indexer, and an OpenSearch mapping. Also note the project's standing hazard — any free-text attribute made filterable breaks OpenSearch search entirely (SearchAttributeGuard exists for exactly this).

### `plp-toolbar-incomplete` — Product-list toolbar has no per-page control and the pager is hardcoded decoration

**Evidence.** make.js:8563-8572: `["←", "1", "2", "3", "→"].map((w) => t("button", { className: ... w === "1" ? "bg-[#0f2144] text-white" : ... , children: w }, w))` — no state, no page count. Toolbar shows only `[R.length, " results"]` and the sort select `gc` (`"Featured"`, `"Price: Low to High"`, `"Price: High to Low"`, `"Top Rated"`, `"Newest"`).

**Impact.** No 'Show N per page', no ascending/descending toggle, no 'Items 1-12 of 340' range, and a pager that cannot represent more than 3 pages. On a catalogue claiming `productCount: 3065` the pager as drawn is unusable.

**Fix.** Add a limit select (12/24/36/All), a sort-direction toggle, an 'Items X-Y of Z' range label, and a pager spec with first/last, ellipsis truncation and current-page state. Confirm 'Top Rated' is buildable before shipping the sort option.

**Magento.** Magento's toolbar renders limit + sort + direction + mode + pager as one block; the design keeps mode (grid/list, which maps cleanly to `?product_list_mode`) and sort but silently drops the rest, so the port would have to remove working native controls.

### `product-card-badges-pinned-physically` — Every product-card overlay badge is absolutely positioned with physical `left-*`/`right-*` so nothing mirrors: discount stays top-left, wishlist heart stays top-right

**Evidence.** Discount badge, 6 sites: `"absolute top-2 left-2 bg-red-500 …"` (172,533 and 174,752), `"absolute top-1.5 left-1.5 bg-[#f26522] …"` (177,992), `"absolute top-2.5 left-2.5 …"` (180,005 and 254,489), `"absolute top-1.5 left-1.5 bg-red-500 …"` (311,484 Category).
Wishlist/heart button: `"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow …"` (175,155), `"absolute top-2.5 right-2.5 w-7 h-7 bg-black/30 backdrop-blur-sm …"` (254,754), `"absolute top-3 right-3 bg-white p-2 rounded-full shadow-md hover:bg-red-50"` (372,986 CustomerProfile).
Other pinned overlays: `"absolute bottom-2.5 right-2.5 bg-black/50 …"` (bundle item count, 180,522), `"absolute -bottom-0.5 -right-0.5"` verified tick ×2 (260,204 / 275,865), `"absolute top-2 right-2 bg-white/90 …"` verified chip (406,419 Vendors), `"absolute top-4 left-4 flex flex-col gap-2"` (444,371 Bundle detail), `"absolute top-2 left-2 w-5 h-5 rounded-full … text-[10px] font-extrabold"` rank badge (269,486), `"absolute top-2 left-2 bg-amber-400 …"` #1 badge (275,240).
Header cart count: `"absolute -top-1.5 -right-1.5 bg-[#f26522] text-white text-[9px] font-bold rounded-full w-4 h-4 …", children: "3"`.

**Impact.** In RTL the card's text content right-aligns while the discount badge stays glued to the top-left corner and the wishlist heart to the top-right — the exact inverse of the intended relationship. On the vendor cards the verified tick attaches to the wrong corner of the avatar, and the header cart badge overlaps the wrong side of the trolley icon.

**Fix.** Convert all of these to logical insets: `left-2`→`start-2`, `right-2`→`end-2`, `-right-1.5`→`-end-1.5`, `-bottom-0.5 -right-0.5`→`-bottom-0.5 -end-0.5`, etc. There are 31 absolutely-positioned physical insets in total (15 `left-*`, 16 `right-*`).

**Magento.** Product-card badge positioning in the theme LESS must use `inset-inline-start`, not `left`, so one stylesheet serves both store views.

### `qty-stepper-and-select-dividers-invert` — Quantity stepper uses `border-r` on minus and `border-l` on plus — in RTL both dividers move to the outside, leaving three cells with no separators

**Evidence.** Product page (make.js 295,599 / 296,096):
```
r("div", { className: "flex items-center gap-0 border border-black/15 w-fit", children: [
  t("button", { onClick: () => n(Math.max(1, i - 1)),
    className: "w-10 h-10 … border-r border-black/15 flex items-center justify-center text-lg", children: "−" }),
  t("span", { className: "w-12 h-10 flex items-center justify-center text-sm font-medium text-[#0a0a0a]", children: i }),
  t("button", { onClick: () => n(i + 1),
    className: "w-10 h-10 … border-l border-black/15 flex items-center justify-center text-lg", children: "+" })
] })
```
Same class of bug in the header search category select (make.js 160,539): `className: "bg-white/10 text-white/80 text-xs px-3 py-2.5 border-r border-white/15 focus:outline-none cursor-pointer min-w-[130px] appearance-none"` — a divider drawn on the select's physical right, sitting between the select and the text input only in LTR.

**Impact.** The flex row reverses in RTL so the order becomes [+][qty][−]; `border-r` is now on the rightmost cell and `border-l` on the leftmost, i.e. both hairlines land on the component's outer edge (doubling the existing `border border-black/15`) and the two internal separators disappear. The stepper reads as one undivided box. In the header, the search bar loses the divider between the category dropdown and the query field and grows a stray line at the far right of the bar.

**Fix.** Change `border-r`→`border-e` on the minus button and the header select, and `border-l`→`border-s` on the plus button. Verify the divider stays between cells after the flex reversal.

**Magento.** Magento's qty stepper and the Vnecoms vendor filter bar have the same construction; use `border-inline-end` in the theme LESS.

### `rating-star-three-colours` — Rating stars render in three different colours and three different empty-star colours, plus raw ★/⭐ glyphs

**Evidence.** Filled: `fill-amber-400 text-amber-400` ×9 (5795, 5846, 5958, 7647, 7738, 7874, 7962, 8425, 8542, 10876) = #ffb900; `fill-yellow-400 text-yellow-400` / `text-yellow-400` ×4 (9345 VendorProfile, 9474/9485/9502 CustomerProfile) = #fdc700; `fill-[#c85c2c] text-[#c85c2c]` ×2 (8156 PDP, 10164 Vendors). Empty: `text-gray-200` (#e5e7eb) on most cards, `text-white/15` on the Home dark card (7647), `text-black/15` on PDP (8156). Raw glyphs used as data: `" ★  ·  "` (8314), `" ★ • "` (8746), `{label:"Avg. Rating",val:"4.8 ★"}` (10649), `children:"⭐ Top Vendors This Month"` (7935). Related badges also use the star yellow inconsistently: `"bg-amber-400 text-[#1a4731] text-[9px] font-bold tracking-widest uppercase"` (7819) vs `"bg-amber-400 text-amber-900 text-[9px] font-extrabold uppercase tracking-widest"` (7952) — the latter is **4.12:1**, failing.

**Impact.** The same 4.8-star rating is orange on the PDP, amber on the listing and yellow on the vendor page. The ⭐/★ glyphs render as OS-specific colour emoji on some platforms and as a monochrome glyph on others, so the star colour is not even under the design's control there.

**Fix.** One `--rating-star` and one `--rating-star-empty` token; one Star component; delete `fill-yellow-400` and `fill-[#c85c2c]` usage and replace all raw ★/⭐ text with the component. Fix the `bg-amber-400 text-amber-900` badge to a pairing ≥4.5:1.

**Magento.** Luma renders ratings via `.rating-result:before{content:'★★★★★'}` with `@rating-icon__active__color` / `@rating-icon__color` — a text colour, so a single token maps cleanly. Vnecoms vendor ratings use the same widget; set both in `_theme.less` so vendor and product ratings match.

### `root-font-size-hard-pinned` — `html{font-size:16px}` pins rem to an absolute value and discards the user's browser font-size preference

**Evidence.** `:root{--font-size:16px;--background:#fff;--foreground:#1a1a2e;...}` combined with the base rule `html{font-size:var(--font-size)}`.

**Impact.** Every size token is a rem (`--text-sm:.875rem` etc.), so pinning the root to an absolute 16px makes the whole scale immovable — a user who has set a larger default font size in Chrome/Safari/Firefox gets no change at all. Combined with 188 declarations below 14px and a Latin-only font stack, low-vision users and Arabic readers have no in-page remedy. This is a WCAG 2.1 SC 1.4.4 (Resize Text) failure by construction.

**Fix.** Remove `html{font-size:var(--font-size)}` or set it to `font-size: 100%`, and keep the scale in rem so it tracks the user agent. Keep `--font-size` only as a documentation value.

**Magento.** Magento blank/Luma use `html{font-size:62.5%}` + `body{font-size:1.4rem}`. Shipping both conventions means rem means different things in ported components vs core components — pick one (recommend dropping the 62.5% hack and using `100%` + rem) and record it in `_variables.less` before any porting starts.

### `root-font-size-locked-to-px` — `html{font-size:16px}` overrides the user's browser font-size preference for the entire site

**Evidence.** make.css: `html{font-size:var(--font-size)}` with `--font-size:16px` declared in `:root`. Every named size token is then relative to that fixed base — `--text-xs:.75rem`, `--text-sm:.875rem`, `--text-base:1rem`, `--text-lg:1.125rem`, `--text-2xl:1.5rem`.

**Impact.** A low-vision user who sets their browser default font size to 20px or 24px — the single most common non-assistive accommodation — gets 16px anyway, because an explicit px value on `html` wins over the user preference. Combined with the 117 sub-12px text instances in the build, this is a direct WCAG 1.4.4 Resize Text risk. Page zoom still works, but font-size preference does not.

**Fix.** Delete the `html{font-size:...}` declaration entirely and let the root default to the user's preference; or if a scale anchor is required, use `html{font-size:100%}`. Keep every downstream size in rem so it scales with the user's setting.

### `runtime-computed-alpha-hex` — Colours are computed at runtime by string-concatenating alpha suffixes onto data-driven hex — no CSS equivalent exists

**Evidence.** `style:{backgroundColor: f.color + "15"}` (7922), `style:{backgroundColor: R + "33"}`, `style:{backgroundColor: R + "22"}`, `style:{backgroundColor: R + "18"}`, `style:{color: R + "cc"}`, plus `style:{backgroundColor: u.accentBg}` ×2, `style:{color: f.accent}`, `style:{backgroundColor: f.bg}`, `style:{borderColor: i}`, `style:{color: i}` ×2, `style:{color: R}` ×3, and `style:{backgroundColor: N === 0 ? "#f59e0b" : N === 1 ? "#94a3b8" : N === 2 ? "#b45309" : "#0f2144"}` (podium ranking, 7952 area). The source values come from data objects, e.g. `{label:"Grocery",emoji:"🛒",href:"/category/grocery",color:"#e8f5e9",items:"2,400+ items"}` and `{id:"50",reason:"You searched for MacBook Pro",reasonIcon:Ue,reasonColor:"#6366f1"}`.

**Impact.** None of these colours can be themed, RTL-adjusted, dark-moded, or contrast-audited by touching CSS — they are inline `style` attributes generated in JS, and inline styles beat every stylesheet. The alpha suffixes ('15'=8%, '18'=9%, '22'=13%, '33'=20%, 'cc'=80%) are also five arbitrary opacity steps with no token.

**Fix.** Move all data-driven colour to CSS custom properties set once on the element (`style={{'--tile-tint': token}}`) consumed by a class, or better, replace the per-item hex with a small enum of token class names (`tile--grocery`, `tile--fashion`…). Replace the ad-hoc alpha suffixes with 2–3 named tint tokens.

**Magento.** In Magento these become PHTML/ViewModel output. Any inline `style="background-color:…"` emitted from a template is un-themeable per store view and also trips stricter CSP setups (`style-src`). Convert to class-driven tints before porting, or the Arabic store view and any future vendor skin will be stuck with the English palette.

### `search-bar-under-header` — The Search page's sticky search bar is z-10 under a z-50 sticky header at the same offset

**Evidence.** Layout header: `r("header", { className: "bg-[#0f2144] sticky top-0 z-50 shadow-lg" …})` (5622). Search page: `t("div", { className: "bg-white border-b sticky top-0 z-10" …})` (8594). Both stick to `top: 0`; the header wins on both stacking order and paint order. These are the only three `z-` values in the bundle (`z-50` ×1, `z-10` ×3) — there is no z-index scale.

**Impact.** On the Search page the sticky search field scrolls up and disappears behind the site header instead of pinning below it — the search input becomes unreachable while scrolling results, which is the one control that page exists for.

**Fix.** Give the page-level sticky bar a `top` offset equal to the header height (via a `--header-height` custom property, not a magic number) and a z-index below the header but above content. Define a 5-step z-index scale as tokens (base / sticky / dropdown / overlay / toast) and use it everywhere.

**Magento.** Magento's header height varies with the store-switcher, the message block and (on Vnecoms) the seller bar, so the offset must be computed, not hard-coded. Ship the header as the element that sets `--header-height`.

### `search-drops-query` — Header search discards the typed query, and the search page ignores the URL entirely

**Evidence.** Header submit handler (make.js:5595): `u = (p) => { p.preventDefault(), m("/search"); }` — the state `l` holding the input value is never passed. Search page (make.js:8580): `const [e, a] = z("")` with `autoFocus: !0`, no `useSearchParams`, no read of `location.search`. The category scope select value `c` (`"All Categories"`, `"Grocery"`, …) is likewise never used.

**Impact.** Searching from the header always lands on an empty search page — the core discovery path is broken end to end in the prototype, so no one has validated what a real results page looks like.

**Fix.** Wire the header form to `/catalogsearch/result/?q=<term>` and have the results page render from that param. Then design the results page properly: it currently has no layered nav, no sort, no pager and no result-count-per-facet, whereas Magento gives all of those for free on `catalogsearch_result_index`.

**Magento.** The header's category-scope dropdown has no native equivalent — core quick search has no scope selector. Either drop it or budget a custom controller that maps the scope to a `cat` filter. Also missing: the autocomplete/suggestion dropdown (`search/ajax/suggest`), which Magento ships and users will expect.

### `search-page-has-no-h1` — The Search page has no `<h1>` at all; the Bundles page jumps h1 → footer h4

**Evidence.** Heading scan by component offset. Search (`xc`, offsets 314269–323190) contains only: h2, h2, h2 "Popular Categories", h3, h2, h2 "No products found", h3, h3 — no h1 anywhere in the range. Bundles (`Tc`) contains exactly one heading, `t("h1", { children: "🎁 Bundle Deals" })`, and the next heading in the DOM is the shared footer's `t("h4", { className: "text-[10px] tracking-widest uppercase text-white/35 mb-4", children: p.title })`. NotFound uses `t("h1", { children: "404" })` followed by `t("h2", { children: "Page Not Found" })`.

**Impact.** Fails WCAG 1.3.1 and 2.4.6. The Search page is a primary landing surface for a marketplace; with no h1 a screen-reader user pressing "1" lands nowhere. The Bundles page skips two heading levels into the footer. "404" as an h1 gives no page identity.

**Fix.** Give Search an `<h1>` that reflects the query ("Search results for X" / "Search"). On Bundles, wrap the footer column headings so they are not the next level after h1 — or set the footer headings to h2 consistently. Change the NotFound h1 to "Page not found" and demote "404" to supporting text.

### `section-header-12-configs` — Section headers exist in three families and about twelve configurations, with seven different "see all" labels

**Evidence.** No section-header component. Family A (marketing, Home + Bundles) is a `"flex items-center justify-between mb-4|mb-5"` row with an h2 carrying an inline Playfair style: `"text-xl font-bold text-[#0f2144]"` (7532, 7713, 7847, 7887, 7935) alternating with `"text-lg font-bold text-[#0f2144]"` (7760, 7897); the optional sub-line is `"text-xs text-gray-400"` (7761) or `"text-xs text-gray-400 mt-0.5"` (7848, 7936) or `"text-white/40 text-xs mt-0.5"` (7981); the bottom margin is `mb-4` ×5 and `mb-5` ×5. Family B (editorial) is an eyebrow + big serif: `"text-[10px] tracking-widest uppercase text-[#c85c2c] mb-2"` + `"text-3xl text-[#0a0a0a]"` (8330-8340), `"text-4xl text-[#0a0a0a] mb-5"` (10496, 10784). Family C (blue pages) is a bare `"text-2xl font-bold"` / `"text-xl font-bold"` / `"text-3xl font-bold"` with no eyebrow, no sub-line and no action link.
The trailing action link is labelled seven ways for the same affordance: `"All Deals "`, `"All Stores "` (×2), `"See All "`, `"View All "` (×2), `"All Vendors "`, `"All Brands "`, `"All Bundles "`. One of them takes its colour from data: `style: { color: f.accent }` (7767), pulling four undeclared hexes (#2d7a3a, #9b2c5e, #9b4b7a, #7c5a3a).
Heading levels are also inconsistent — `text-2xl` h1 on Product-not-found (8085) vs `text-5xl` h1 on Vendors (10107) vs `text-9xl` on the 404 (11013).

**Impact.** Vertical rhythm changes between every homepage band (4px difference in section spacing, 4px in heading size), and the same "go to the full list" action is worded seven ways in one scroll — which reads as seven unrelated destinations.

**Fix.** One SectionHeader: `eyebrow?`, `title`, `subtitle?`, `action?: {label, href}`, `tone: 'light' | 'dark'`, `size: 'sm' | 'md' | 'lg'`. One bottom-margin token. Standardise the action label to a single string ("View all") and let the destination carry the meaning. Remove the data-driven `style: { color: f.accent }`.

**Magento.** Homepage bands in Magento are CMS blocks authored by merchandisers. Give them one header partial with named fields; twelve variants means merchandisers will hand-write headings into WYSIWYG and the rhythm will drift further after launch.

### `star-rating-11-implementations` — Eleven inline star-rating loops, four sizes, three colours, and two that always render 5/5

**Evidence.** `[...Array(5)].map` appears 11 times, each re-implementing the rating (5795, 5846, 5958, 7647, 7874, 8153, 8425, 8542, 9474, 9502, 10876). Sizes: `w-2.5 h-2.5`, `w-3 h-3`, `w-3.5 h-3.5`, `w-4 h-4`, `w-5 h-5`. Filled colour: `"fill-amber-400 text-amber-400"` ×10, `"fill-yellow-400 text-yellow-400"` (9345), `"fill-[#c85c2c] text-[#c85c2c]"` on the PDP (8155) and Vendors page (10273). Empty colour: `"text-gray-200"` (8×), `"text-white/15"` (7647), `"text-black/15"` (8155). Two implementations ignore the rating entirely and hard-code five filled stars for every review: `{ className: "flex text-yellow-400 mb-1", children: [...Array(5)].map((s, c) => t(me, { className: "w-5 h-5 fill-current" }, c)) }` (9474) and the same at 9502. Every implementation floors: `l < Math.floor(e.rating)` — so 4.9 and 4.7 both render as 4 stars, and no half-star exists. Separately, the PDP vendor box renders the rating as a literal glyph: `u.rating, " ★  ·  ", u.totalProducts, " products"` (8316).

**Impact.** On the VendorProfile every review shows 5 stars regardless of its score — a factual misrepresentation of seller ratings on a marketplace. Elsewhere a 4.9-rated product looks identical to a 4.0-rated one. Amber vs yellow vs terracotta stars on adjacent pages read as three different rating systems.

**Fix.** One Rating component: props `value: number`, `max = 5`, `size: 'xs'|'sm'|'md'|'lg'`, `showValue`, `showCount`, `tone: 'onLight'|'onDark'`. Support half-stars (round to nearest 0.5, not floor). Wire the two VendorProfile review blocks to the real review score. Pick one filled colour token and one empty colour token.

**Magento.** Magento renders ratings as a percentage-width overlay (`.rating-result` with `style="width:94%"`), which gives exact fractional ratings for free and is a single template. Build it once as a ViewModel-backed partial and reuse it for product, vendor and review contexts — do not transcribe 11 loops.

### `star-rating-not-perceivable` — The star rating is 1.67:1 filled, 1.24:1 empty, 1.35:1 filled-vs-empty, and has no text alternative

**Evidence.** ProductCard, make.js: `[...Array(5)].map((n, l) => t(me, { className: `w-3 h-3 ${l < Math.floor(e.rating) ? "fill-amber-400 text-amber-400" : "text-gray-200"}` }))`. amber-400 (#fbbf24) on white = 1.67:1; gray-200 (#e5e7eb) on white = 1.24:1; the two states against each other = 1.35:1. The compact card variant renders the five icons with no adjacent text at all; the default variant adds only `r("span", { className: "text-[10px] text-gray-400 ml-0.5", children: ["(", e.reviews, ")"] })` = 2.54:1. Elsewhere the rating is typed as a bare glyph: `children: [u.rating, " ★  ·  ", u.totalProducts, " products"]`.

**Impact.** Two independent failures. Visually, a low-vision user cannot see how many stars are filled — the contrast between the filled and empty state is 1.35:1, far below the 3:1 required for meaningful non-text content (1.4.11). Programmatically, a screen reader on a compact card hears nothing at all, and on a default card hears "(128)". Where the glyph is used it is announced literally as "4.8 black star middle dot 120 products". Fails 1.1.1, 1.4.11 and 1.4.1.

**Fix.** Wrap the star row in `role="img"` with `aria-label={`${e.rating} out of 5 stars, ${e.reviews} reviews`}` and mark the individual icons `aria-hidden`. Darken the filled state to `#b45309` (amber-700, 4.9:1) or add a `stroke` outline, and change the empty state from gray-200 to a stroked gray-500 outline so filled-vs-empty exceeds 3:1. Replace every literal `★` with the labelled component.

### `sub-14px-text-epidemic` — 188 of 475 sized text declarations (39.6%) are below 14px, down to 7px, including live commerce data

**Evidence.** All five arbitrary steps are compiled: `.text-\[7px\]{font-size:7px}` `.text-\[8px\]{font-size:8px}` `.text-\[9px\]{font-size:9px}` `.text-\[10px\]{font-size:10px}` `.text-\[11px\]{font-size:11px}`. Counts in make.js: 10px ×55, 9px ×37, 11px ×18, 8px ×4, 7px ×3, plus `text-xs` (12px) ×71. Live-UI (not mockup) examples: header icon labels `t("span", { className: "text-[9px] tracking-wide", children: "Account" })` / `"Wishlist"` / `"Cart"`; the strikethrough was-price on a card `r("span", { className: "text-[9px] text-gray-400 line-through ml-1", children: [ "$", e.originalPrice ] })`; verified badge `t("p", { className: "text-[9px] text-[#f26522] font-bold uppercase tracking-wider", children: "✓ Verified" })`; footer copyright `"border-t border-white/8 pt-6 ... text-[11px] text-white/25"`; the app's ONLY h4 is the footer column heading at `text-[10px]`.

**Impact.** The 9px strikethrough was-price is the discount proof — commercially the second most important number on a card — set 5px below the platform minimum. The 9px header icon labels are the primary account/wishlist/cart affordance. In Arabic this is materially worse: Naskh forms carry their distinguishing marks (nuqat) at roughly a third the size of the letter body, so Arabic needs ~1.15–1.3× the Latin size for equal legibility; a 9px Arabic label is effectively a 7px Latin one.

**Fix.** Set a hard floor: 12px for purely decorative/meta text, 14px for anything a customer reads to make a decision (prices, was-prices, vendor names, stock, delivery promise, badges, nav labels). Delete the 7px/8px/9px/10px/11px steps from the design entirely. The three phone-mockup blocks that account for the 7px/8px uses should be rendered as scaled-down real components (`transform: scale()`) or SVG, never as hand-set 7px live text.

**Magento.** Luma's base is `body{font-size:1.4rem}` (14px) with `html{font-size:62.5%}` — align the floor to that so ported components don't need per-element overrides.

### `success-green-untokenised-and-failing` — No success token; green-600 (#00a63e, 3.22:1) is the de-facto success colour and is also used as a button fill under white text

**Evidence.** `text-green-600` ×18, `bg-green-600` ×4, plus `green-700`, `green-100`, `green-500`, and a fifth green `#2d7a3a` ×5 (`{icon:ha,label:"Secure Payments",sub:"Tap, Visa, Tabby BNPL",color:"#2d7a3a"}` 7402, `{title:"🛒 Grocery Essentials",…,bg:"#f0f7f1",accent:"#2d7a3a"}` 7387, hero `accentBg:"#2d7a3a"`, `linear-gradient(135deg, #1a4731 0%, #2d7a3a 100%)` 7814). green-600 resolves from `--color-green-600:oklch(62.7% .194 149.214)` = **#00a63e**; contrast on white **3.22:1**, and white-on-green-600 is also **3.22:1**. Failing usages: `t("span",{className:"text-green-600",children:"FREE"})` (8907), `t("p",{className:"text-xs text-green-600",children:"🎉 You qualify for free shipping!"})` (8909), `"text-xs font-bold text-green-600"` 'Save $X' (5908, 5977), `` `${e.stock > 0 ? "text-green-600" : "text-red-500"}` `` (10681), `t("span",{className:"text-green-600",children:"✓"})` ×3 (8946/8950/8954), and the button `"flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700"` (Place Order, 9241). `bg-green-100 text-green-700` (#dcfce7/#008236) = 4.50:1, only just passing.

**Impact.** 'FREE', 'You qualify for free shipping', savings amounts and in-stock status — the highest-value positive signals on the page — are all below AA, and the Place Order button's white label is at 3.22:1. Also #2d7a3a (5.31:1) is a perfectly compliant green already present in the design but never used for status.

**Fix.** Add `--success` = #2d7a3a (5.31:1 on white, already in the palette) and `--success-strong` for fills where white text sits on top (needs ≥4.5:1 — #2d7a3a gives 5.31:1). Replace every green-600/green-700/green-500 usage. Stop using green as a button fill for the money-spending action (see the CTA finding).

**Magento.** Maps to `@message-success__color` / `.message.success` and to `.stock.available` in Magento. Note Luma's default success green also fails AA — do not inherit it; set the token explicitly.

### `tax-rate-hardcoded-10-percent` — Cart hard-codes "Tax (10%)" — not the VAT rate of any of the five target countries except Bahrain

**Evidence.** Cart (make.js 329,423): `t("span", { className: "text-gray-600", children: "Tax (10%)" })` with `r("span", { className: "font-medium", children: ["$", c.toFixed(2)] })`. Checkout (348,568) uses the unlabelled `children: "Tax"`. No country/region input exists to derive a rate from (see `checkout-address-form-is-us-locale`).
Marketing copy elsewhere claims the opposite: `"PCI-DSS ready checkout, GDPR-aware data handling, and Gulf VAT compliance."` (Vendors 414,716) and `"…Arabic-ready, VAT-compliant, mobile-first."` (footer 167,780).

**Impact.** Actual rates: UAE 5%, KSA 15%, Qatar 0%, Bahrain 10%, Kuwait 0%. A hard-coded 10% is wrong for four of the five markets, and the label bakes the rate into a translatable string so a translator will ship the wrong number in Arabic too. Under RTL the label also scrambles: `"Tax (10%)"` renders as `(Tax (10%` with the closing bracket detached and mirrored to the far left.

**Fix.** Remove the rate from the label. Render `Tax` / `VAT` as a bare label plus a separate formatted amount, and derive the rate from the selected country. If a rate must be shown, build it as a single interpolated phrase (`__('VAT (%1%%)', rate)`) so it can be translated and reordered as a unit, and wrap the amount in `<bdi>`.

**Magento.** Magento's tax engine handles this properly per website via tax rules and `Magento\Tax\Model\Calculation`. Never render a literal rate in a template. For Vnecoms multi-vendor, vendor-level VAT registration also affects whether tax is charged per sub-order — the design's single flat tax line cannot represent a split-vendor cart.

### `tiny-type-and-letterspacing` — 117 text instances render below 12px, 42 of them combined with `tracking-widest`

**Evidence.** Counts from make.js class strings: `text-[10px]` × 55, `text-[9px]` × 37, `text-[11px]` × 18, `text-[8px]` × 4, `text-[7px]` × 3 — 117 total below 12px, plus 71 `text-xs` (12px). `tracking-widest` appears 42 times, repeatedly stacked on the smallest sizes: `"text-[10px] tracking-widest uppercase text-white/35 mb-2"`, `"text-white text-[10px] font-bold tracking-widest uppercase px-3 py-1 rounded-full"`, `"text-[9px] font-bold tracking-widest uppercase px-2 py-0.5 rounded"`, `"text-[10px] tracking-widest uppercase text-[#c85c2c] border border-[#c85c2c]/40 px-2 py-0.5"`.

**Impact.** 9–11px uppercase text at 0.1em tracking is at the edge of legibility for normal vision and below it for anyone with mild presbyopia. Most of these instances are simultaneously the low-contrast ones flagged above, compounding the problem. This also collides with WCAG 1.4.12 Text Spacing, which requires the layout to survive a 0.12em letter-spacing increase — several of these are already at 0.1em inside `whitespace-nowrap` badges.

**Fix.** Set a hard floor of 12px for any text a user must read and 14px for body copy; reserve 10–11px for genuinely decorative or duplicated labels only. Cap `letter-spacing` at 0.05em below 14px. Critically for this project, remove `tracking-widest` from any element that can carry Arabic — letter-spacing breaks Arabic cursive joining and renders words as disconnected letterforms.

**Magento.** Add a guard in the ar_SA theme's LESS: `[lang="ar"] { letter-spacing: normal !important; }` scoped to the tracking utility classes.

### `touch-targets-under-minimum` — At least 14 distinct interactive controls compute below 44px, and four fall under the WCAG 2.2 hard floor of 24px

**Evidence.** Computed from the literal padding/size classes in make.js. Under 24×24 (fails SC 2.5.8 AA): carousel slide dots `"h-1.5 rounded-full ... w-1.5"` = 6×6px inactive, 24×6px active; the top utility-bar links `t(C, { to: "/profile", className: "px-3 hover:text-white transition-colors", children: "Login" })` inside a `"bg-[#091830] text-white/65 text-xs py-1.5"` band — no vertical padding on the link itself, so the target box is the 16px `text-xs` line box; footer links `"text-xs text-white/55 hover:text-white transition-colors"` in `<li>` with `space-y-2.5` — also a 16px line box. Under 44px: quick-add `"w-6 h-6 bg-[#f26522] ..."` = 24px; vendor-card wishlist `"w-7 h-7 ..."` = 28px; card wishlist `"w-8 h-8 ..."` = 32px; cart quantity ±  `"p-2 hover:bg-gray-50"` + `w-4 h-4` = 32px; grid/list toggles `"p-2 transition-colors"` + `w-4 h-4` = 32px; search tabs `` `pb-2 px-1 ...` `` = 32px; Filters toggle `"md:hidden flex items-center gap-1.5 text-sm border border-gray-200 px-3 py-1.5 rounded-lg ..."` = 32px; carousel arrows, pagination and cart-remove `"w-9 h-9"`/`p-2 w-5 h-5` = 36px; header Account/Wishlist/Cart `"min-w-[40px]"` with a 20px icon + `text-[9px]` label ≈ 36px.

**Impact.** The 6px carousel dots and the 16px-tall top-bar and footer links are outright SC 2.5.8 failures — no exception applies because these are discrete nav items, not links inline in a sentence. The 24–36px group fails SC 2.5.5 (AAA) and the WCAG 2.2 44px best practice, and on a Gulf marketplace where mobile is the dominant channel this directly costs conversions on cart quantity edits.

**Fix.** Set a project-wide rule that any control's hit area is at least 44×44 CSS px, achieved with padding or a transparent `::before{position:absolute;inset:-Npx}` overlay that does not change the visual size. Priority order: cart quantity ± and remove, carousel dots and arrows, quick-add, top-bar and footer links, filter/sort toggles.

### `type-scale-bypassed` — 15 rendered sizes against a 10-token scale; 117 uses bypass the token system entirely and the scale has a 48px→128px hole

**Evidence.** Declared tokens in `@theme`: `--text-xs:.75rem --text-sm:.875rem --text-base:1rem --text-lg:1.125rem --text-xl:1.25rem --text-2xl:1.5rem --text-3xl:1.875rem --text-4xl:2.25rem --text-5xl:3rem --text-9xl:8rem`. `--text-6xl`, `--text-7xl`, `--text-8xl` are never emitted, but `--text-9xl:8rem` is — for a single element: `t("h1", { className: "text-9xl font-bold text-blue-600 mb-4", children: "404" })`. Actual rendered set including the arbitrary steps: 7, 8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 30, 36, 48, 128 px. Distribution of the 475 size declarations: text-sm 158, text-xs 71, text-[10px] 55, text-[9px] 37, text-xl 34, text-lg 26, text-2xl 21, text-3xl 19, text-[11px] 18, text-4xl 13, text-5xl 9, text-base 6, text-[8px] 4, text-[7px] 3, text-9xl 1.

**Impact.** Fifteen steps is roughly double what a storefront can hold consistent, and five of them (117 declarations, 25%) are arbitrary values that no token can re-scale — a future "increase base size by 1px" change would miss a quarter of the text. The declared 16px base is fiction: `text-base` is used 6 times in the whole app while 14px is used 158 times and 12px 71 times. The real base size is 14px. The scale also jumps 48px → 128px with nothing between.

**Fix.** Collapse to 8 tokens and publish them as the only permitted sizes: 12 / 14 / 16 / 18 / 20 / 24 / 32 / 44. Ban arbitrary `text-[Npx]`. Set the documented base to 14px (which is what the design actually does) rather than 16px. Replace `text-9xl` on the 404 with the 44px token.

**Magento.** Express the 8 steps as LESS variables (`@font-size__xs` … `@font-size__xxxl`) in `_variables.less` so Vnecoms and third-party module overrides can reference them instead of hardcoding px.

### `vendor-route-and-dead-ids` — Vendor microsite uses /vendor/<numeric-id> instead of /shop/<key>, and 6 of the 10 seller cards link to a 'Vendor not found' page

**Evidence.** Directory builds `Pt = [ ...at, { id: "5", name: "Apex Sport" }, … { id: "10", name: "Noura Couture" } ]` (make.js:10018-10092) and links each card with ``to: `/vendor/${u.id}` ``. But the vendor page resolves against the 4-entry array: make.js:9307 `const a = at.find((s) => s.id === e)`, falling through to `children: "Vendor not found"`.

**Impact.** Apex Sport, Lumière Beauty, BrainSpark Toys, Gulf Distributors, Al Meera Fresh and Noura Couture — 6 of the 10 sellers on the directory — dead-end. Nobody has actually reviewed the vendor page against the vendors shown in the directory.

**Fix.** Fix the data so every directory card resolves, and change the URL shape to a seller key (`/shop/<key>`) rather than a numeric id.

**Magento.** Vnecoms serves the microsite at /shop/<url_key> and the directory at /sellerlist; numeric-id URLs would need a rewrite layer and cost the SEO value of the seller name in the path.

### `vendor-tabs-missing-policies` — Vendor page bakes store policies into hardcoded prose instead of the per-vendor Shipping/Refund tabs Vnecoms provides

**Evidence.** make.js:9430-9460: under `children: "Store Policies"` the page hardcodes `"30-Day Returns"` / `"Easy returns within 30 days of purchase"`, `"Fast Shipping"` / `"Most orders ship within 1-2 business days"`, `"Quality Guaranteed"`, plus a hardcoded address `"123 Business St, Commerce City, ST 12345"` and `"Response time: Within 24 hours"`. Tabs are only `"Products"`, `"About"`, `"Reviews"`.

**Impact.** Every vendor's storefront would show identical, false policy text and a US address — in a marketplace where each seller sets their own shipping and refund terms and is legally responsible for them.

**Fix.** Add per-vendor Shipping Policy and Refund/Return Policy tabs fed by vendor-editable content, and design the empty state for vendors who have not filled them in. Replace the hardcoded address with real vendor location fields.

**Magento.** Vnecoms already stores vendor-authored About/Shipping/Refund pages — the design is throwing away data the platform has. There is also no product search, no category filter and no pager on the vendor's product grid, which the microsite listing needs.

### `wishlist-and-compare-absent` — Wishlist is a header button that links nowhere; product compare does not exist at all

**Evidence.** make.js:5658-5662: `r("button", { className: "hidden md:flex flex-col items-center gap-0.5 text-white/65 …", children: [ t(et, {…}), t("span", { …, children: "Wishlist" }) ] })` — a `<button>` with no `to`, no href, no count badge. In the wishlist tab, the add-to-cart control is ``t(C, { to: `/product/${l.id}`, className: "bg-blue-600 text-white px-4 py-2 rounded-lg …", children: "Add to Cart" })`` — a link to the PDP, not an add. `grep -c 'Compare' make.js` = 0.

**Impact.** Wishlist has no page, no counter, no share, no add-all-to-cart, and no qty/comment fields. Compare — which Magento ships with a sidebar block, a compare page and a PLP action — is absent, so porting means deleting native functionality rather than styling it.

**Fix.** Design `wishlist/index/index` as a real page (grid, per-item qty + comment, Add to Cart, Move to Cart, Share Wishlist, empty state) and give the header link a live item count. Decide explicitly whether Compare is cut; if kept, design the PLP action, the counter and the compare table.

**Magento.** The wishlist and cart counters must both come from `Magento_Customer/js/customer-data` sections, not server-rendered — the header is inside the FPC-cached page. The design's hardcoded cart badge `children: "3"` (make.js:5668) is the same issue.

### `zero-accessible-names` — Zero aria-labels, zero roles, zero sr-only text in the whole bundle — every icon-only control is unnamed

**Evidence.** Across 460KB: `aria-label` → 0, `aria-labelledby` → 0, `aria-describedby` → 0, `aria-live` → 0, `role:` → 0, `tabIndex` → 0, `onKeyDown` → 0. The only `aria-*` is `"aria-current": a = "page"` inside react-router's own `NavLink` implementation (4697), which the app never uses. `sr-only` is compiled into make.css but has 0 uses in the JS.
Unnamed icon-only controls: 5 hero carousel dots (empty `<button>` with className + onClick only, 7474); carousel prev/next (7482, 7490); the header hamburger `{ className: "md:hidden text-white", onClick: () => a((p) => !p), children: e ? t(nc, …) : t(Po, …) }` (5670); the mobile search submit `"bg-[#f26522] text-white px-4"` containing only a magnifier (5684); wishlist heart (5836, 7641, 9708); quick add-to-cart (5808, 5867, 7666); cart remove `"text-red-600 hover:text-red-700 p-2"` (8862); quantity ±  (8871, 8880); grid/list toggle (8488, 8496); PDP share and wishlist (8293, 8294); PDP gallery thumbnails (8123, whose `<img>` carries `alt: ""`).
The header cart badge is a bare `"3"` — `{ className: "absolute -top-1.5 -right-1.5 … w-4 h-4 …", children: "3" }` (5666) — with no accessible text saying what the 3 counts.
Only 1 of 36 `<img>` has an intentionally empty alt; the rest reuse the product/vendor name, so decorative banner images are announced as content.

**Impact.** A screen-reader user hears "button" for every control on the homepage carousel, the header, and every product card. Nothing announces that filtering or sorting changed the results (no `aria-live` region), so keyboard and screen-reader users cannot tell a filter took effect. This is not a partial gap — it is a complete absence of the accessible-name layer.

**Fix.** Instruct Figma Make to give every icon-only control an `aria-label` ("Add {product} to cart", "Add {product} to wishlist", "Remove {product} from cart", "Increase quantity", "Go to slide 2 of 3", "Open menu", "Search"), give the cart badge visually-hidden text ("3 items in cart"), set `alt=""` on decorative banners, add `aria-live="polite"` to the results-count region on Category and Search, and add `aria-current="page"` to the active nav item and pagination page.

**Magento.** Magento core templates already carry `<span class="label">`/`aria-label` on most of these controls (minicart, pager, sorter, swatches). If the design supplies no accessible names, an implementer stripping core markup for pixel fidelity will silently delete them. Make the accessible names part of the design spec, not an implementation afterthought.


## P2

### `ai-recommendations-need-engine` — 'AI Engine / Picked For You' promises per-user reasoned recommendations with no engine behind it

**Evidence.** make.js:7392-7400: `St = [ { id: "50", reason: "You searched for MacBook Pro", …}, { id: "32", reason: "Popular in Beauty & Health" }, { id: "1", reason: "Frequently bought together" }, { id: "34", reason: "Matches your style profile" } ]`, plus `hc = ["MacBook Pro", "Air Fryer", "Vitamin C Serum", "Gaming Setup", "Espresso Machine"]` rendered under `children: "Your searches"`, and the strapline `"Personalised recommendations based on your search history & behaviour"`.

**Impact.** Four distinct recommendation types are implied, each needing a different data source: search history, category popularity, co-purchase analysis, and a 'style profile' that has no definition anywhere. The section also sits on a full-width indigo/purple band (`linear-gradient(90deg, #6366f1, #a855f7)`) that is outside the brand palette.

**Fix.** Scope this down to what is achievable: 'Recently Viewed' (native `Magento_Reports` widget, real per-user data) and 'Customers who bought this also bought' (native related/upsell, admin-curated). Cut the 'style profile' and 'Your searches' chips or back them with real stored data.

**Magento.** Magento has no recommendation engine in Open Source. Recently Viewed and Related/Upsell/Cross-sell blocks are the free options and would fill this section honestly with a fraction of the work.

### `app-shell-inline-font-family` — The body font-family is declared twice — once in CSS and once as an inline style on the app shell

**Evidence.** make.css base: `body{font-family:DM Sans,system-ui,sans-serif}`. make.js root wrapper: `r("div", { className: "min-h-screen bg-[#f5f7fa]", style: { fontFamily: "'DM Sans', system-ui, sans-serif" }, children: [ ... ] })` — note the two declarations also differ in quoting (`DM Sans` unquoted vs `'DM Sans'`).

**Impact.** Two sources of truth for the single most fundamental typographic decision, one of them an inline style that no stylesheet can override on that node. Any locale-conditional font swap (the Arabic work above) has to be applied to descendants rather than the shell, and any attempt to change the base family will appear to half-work.

**Fix.** Delete the inline `style={{ fontFamily: ... }}` from the app-shell div and keep the single `body` rule in CSS, so `:lang(ar)` / `[dir="rtl"]` can override the family in one place.

**Magento.** In Magento the family belongs in exactly one place — `@font-family-name__base` / `@font-family__base` in `_variables.less`. Never in a template's inline style; `Magento\Csp` in strict mode will also strip inline styles.

### `arabic-rtl-marketing-claims-unimplemented` — The Features and Platform pages sell Arabic/RTL/Hijri capability that the design itself implements none of

**Evidence.** Vendors/Features data (make.js 412,730–413,074):
```
{ icon: Ka, title: "Arabic & RTL Ready",
  desc: "Full Arabic-language interface with right-to-left layout. Switch between Arabic and English in one click. All admin, vendor, and buyer panels are fully bilingual.",
  bullets: ["Full Arabic translation", "RTL layout support", "Bilingual admin panel", "Islamic calendar support"] }
```
Also `"…Push notifications, deep links, and Arabic UI included."` with bullet `"Arabic RTL mobile UI"` (412,482), `"Arabic & English UI with RTL"` (Platform 436,733), `"Switch between Arabic and English UI"` (439,472), footer `"…Arabic-ready, VAT-compliant, mobile-first."` (167,780), Home app banner `"Browse 50,000+ products from 500+ verified sellers. Arabic & English…"` (280,780).
Against: 0 translated strings, 65 physical direction utilities, 0 logical properties, 0 `[dir=rtl]` CSS rules, no Arabic font, no Hijri/`Intl.DateTimeFormat` calendar handling anywhere.

**Impact.** Six separate on-page claims that the platform is Arabic/RTL/bilingual/Hijri-ready, on the two pages a prospective vendor or investor is most likely to read, backed by an implementation that fails all four. If this design ships as-is, the Arabic store view is the single most obvious contradiction of the sales pitch on the site.

**Fix.** Either implement the four bullets (translation layer, logical CSS, Arabic font, Hijri-capable date formatting) or remove the claims. At minimum drop `"Islamic calendar support"` — nothing in the design touches calendars beyond bare `toLocaleDateString()`.

**Magento.** Magento can genuinely deliver all four (store-view translation, RTLCSS, per-locale fonts, ICU Hijri via `general/locale/code`), so the claims are achievable — but they must be tracked as build scope, not assumed from the design.

### `badges-and-countdown-need-attributes` — Product badges and the deal countdown assume catalog fields Magento does not have

**Evidence.** Bundle/product badges are free-text per item: `badge: "Save $200"`, `badge: "Bestseller"`, `badge: "Same-Day"`, `badge: "Pharmacist Pick"`, `badge: "Top Gift"`, `badge: "Great Value"` (make.js:7194-7364). The home countdown is a fake local timer: `z({ h: 5, m: 23, s: 45 })` decrementing on `setInterval` and resetting via `--S < 0 && (S = 5)`.

**Impact.** Nine different badge strings across the design imply an editorial labelling system with no storage, and the 'Today's Deals' countdown resets every 5 hours regardless of any real promotion window — so it would show a countdown to nothing.

**Fix.** Add one `product_label` attribute (select, with a fixed option list — resist free text) and render the badge from it. Drive the countdown from a real end timestamp rather than a client-side timer, and design the post-expiry state.

**Magento.** `special_to_date` is already native and is the natural countdown source — a deal block filtered on `special_price` with `special_to_date` in the future gives both the deal list and the timer with zero new schema.

### `breadcrumb-slash-separator-and-text-left` — Breadcrumb separators are literal `/` spans, and two interactive elements are hard-coded `text-left`

**Evidence.** Seven breadcrumb separators, all `t("span", { children: "/" })` inside a `flex items-center gap-2` row: Product 286,952 and 287,172, Category 302,130, Checkout 332,356, Bundle detail 443,508 and 443,664, Bundles index 453,015. U+002F SOLIDUS has Bidi_Mirrored=No, so the glyph keeps its right-leaning slant when the crumb order reverses.
`text-left` ×2: the mobile language toggle itself — `t("button", { onClick: g, className: "block py-1 text-white/65 hover:text-white text-left w-full", children: i ? "Switch to English" : "التحويل إلى العربية" })` (166,602) — and the Search page suggestion rows — `className: "w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3"` (317,897).

**Impact.** In the Arabic view a right-leaning `/` sits between crumbs that now run right-to-left, which reads as pointing back into the previous level. The `text-left` on the language toggle is the sharpest instance: the one button whose whole job is to enter Arabic mode left-aligns its own Arabic label. Search suggestions likewise left-align Arabic query text against the panel's trailing edge.

**Fix.** `text-left`→`text-start` on both. For the separator, use `›` (U+203A, Bidi_Mirrored=Yes, so it flips automatically) or a CSS `::after` chevron that can be mirrored, instead of `/`.

**Magento.** Magento's default breadcrumb separator is a CSS `content` glyph in `_breadcrumbs.less`; make sure it is a mirrorable character or gets an `[dir=rtl]` override.

### `category-taxonomy-inconsistent` — Category slugs are inconsistent: one link targets a slug that does not exist, and cosmetics is duplicated

**Evidence.** Defined slugs are `technology, fashion, home-living, sports-outdoors, beauty-health, grocery, pharmacy, fmcg, kids, home-appliances, cosmetics`. But the hero slide and a category tile use `href: "/category/electronics"` (2 occurrences), which is undefined — the category page then silently falls back to the whole catalogue via `S.length === 0 && (S = J)`. The header menu maps `{ label: "Cosmetics", href: "/category/beauty-health" }` even though a separate `slug: "cosmetics"` category exists.

**Impact.** In the prototype a broken slug shows every product under a wrong heading; in Magento it is a 404. And two categories for the same concept means split product assignment, split layered-nav facets and duplicate-content SEO penalties.

**Fix.** Freeze one canonical slug per category and use it everywhere. Decide whether Electronics & Tech and Home Appliances are siblings or parent/child, and whether Cosmetics is the same node as Beauty & Health.

**Magento.** This is the category tree spec — settle it before catalogue import, because re-parenting categories after products are assigned means re-indexing and a url_rewrite cleanup.

### `color-scheme-declared-dark-with-no-dark-styles` — The shell advertises `color-scheme: light dark` but the stylesheet has zero `prefers-color-scheme` rules

**Evidence.** make.html: `<meta name="color-scheme" content="light dark" />`. make.css: `grep -c 'prefers-color-scheme'` = 0, and there is no `color-scheme` CSS declaration anywhere. The only `.dark` rule that survives compilation is `.dark\:focus-visible\:ring-destructive\/40:is(.dark *):focus-visible{…}`; nothing ever adds the `.dark` class. The oklch block in the CSS is unreferenced shadcn dark-mode default.

**Impact.** A user with OS dark mode gets dark-rendered native UI — `<select>` dropdown popups (there are 6 selects, including the header category filter and the PDP variant pickers), scrollbars, autofill highlights and date pickers — layered on hard-coded white/`#f5f7fa` page chrome. The result is low-contrast or inverted native controls the design never accounted for, most visibly on the PDP variant select where option text would render light-on-light.

**Fix.** Either remove the meta tag and add `:root{color-scheme: light}` so native controls stay light, or implement a real dark theme. Given that the brand palette is defined only for light, the first option is correct for launch.

### `container-and-radius-drift` — Two container paddings, two container widths, and a radius scale that ignores the declared `--radius`

**Evidence.** Container: `"max-w-7xl mx-auto px-4"` ×36 (header, footer, Home, Category, Search, Cart, CustomerProfile, Bundles) vs `"max-w-7xl mx-auto px-6"` ×12 (Product 8111, Vendors 10100/10129/10173, Features 10405/10457/10485, Platform 10541/10585/10723). Checkout departs again with `"max-w-6xl mx-auto px-4"` (8970). So on every editorial page the content is inset 8px further than the header and footer that frame it, and navigating to Checkout narrows the page by 128px.
Radius: `--radius:.75rem` is declared and `var(--radius)` is consumed 25 times in the compiled CSS, but the JS uses `rounded-lg` ×112, `rounded-full` ×63, `rounded-xl` ×27, `rounded-2xl` ×8, `rounded` ×13, `rounded-md` ×1, `rounded-[2rem]` ×1 — none of which resolves to `--radius`. Cards are `rounded-xl` in the brand system, `rounded-lg` in the blue system and square (no radius class) in the editorial system.

**Impact.** The page content visibly steps in and out relative to the fixed header on every navigation between an editorial page and a brand page, and the page width jumps on entering checkout. Six radius values across three systems means "how round is a card" has no answer.

**Fix.** One container token (`max-w-7xl` + one horizontal padding, responsive) used by header, footer and every page including Checkout. One radius scale of three steps (sm/md/full) derived from `--radius`, and one card radius chosen for the whole storefront.

**Magento.** Magento's `.page-main` / `.columns` container is set once in the theme's LESS. If the design has two paddings and three widths, the implementer will add per-page overrides in `_module.less` files that then drift independently.

### `emoji-icon-system-is-not-localisable` — The emoji icon system includes glyphs that are direction-bearing or contain Latin text and cannot be localised

**Evidence.** `"🆕 New Stores on MECommerce"` (Home 277,245) — U+1F195 SQUARED NEW has Bidi_Class=L and renders the Latin word "NEW" inside the glyph; it cannot be translated to Arabic.
`t("span", { className: "text-2xl leading-none", children: "▶️" })` (Home 281,895) — U+25B6 is direction-bearing and Bidi_Mirrored=No, so the app-store play affordance keeps pointing right in RTL.
`"🔍"` (U+1F50D LEFT-POINTING MAGNIFYING GLASS) used 4× as the empty-state and bottom-nav search icon — a left-pointing glyph in a right-to-left UI.
All 21 emoji-prefixed labels transpose under RTL: `"🛒 Grocery"` → `Grocery 🛒`, `"🎁 Bundle Deals"` → `Bundle Deals 🎁`, `"🏆 Best Selling Items"` → `Best Selling Items 🏆`, `"✓ Verified"` → `Verified ✓`, `"🏷️ Today's Deals"` → `Today's Deals ️🏷` (the U+FE0F variation selector separates from its base in the reordered run).

**Impact.** The "NEW" badge is permanently English. Directional emoji point the wrong way. Emoji-plus-label strings reverse, which is correct RTL behaviour but was never designed for — several labels were composed as `p.emoji + " " + p.label` (footer category links, 168,600) and will need the separator re-thought. The `🏷️`/`🛋️` variation-selector split risks the glyph falling back to monochrome text presentation.

**Fix.** Replace the functional emoji with the lucide SVG set already bundled (there are 46 lucide icons in the file including `search`, `tag`, `check`, `star`, `heart`, `gift`-equivalents). Drop `🆕` in favour of a translated text badge. Where emoji remain decorative, render them as a separate flex child rather than string-concatenating (`p.emoji + " " + p.label`) so the gap is controlled by `gap-*` and the variation selector is never reordered.

**Magento.** An SVG sprite in the theme is also cheaper than emoji font fallback, which is inconsistent across the Android devices dominant in the Gulf.

### `font-payload-and-untokenised-weights` — DM Sans 300 and both Playfair italics are downloaded and never used; the three weights the UI actually relies on are untokenised Tailwind defaults

**Evidence.** `@import"https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap"`. `font-light` appears 0× in make.js and `--font-weight-light` is not defined anywhere in make.css. The only `fontStyle: "italic"` in the bundle is React Router's internal error screen (`O("h3", { style: { fontStyle: "italic" } }, a)`), so Playfair `1,400;1,600` are never rendered. Meanwhile `:root` declares only `--font-weight-medium:500;--font-weight-normal:400`; `--font-weight-bold:700`, `--font-weight-semibold:600` and `--font-weight-extrabold:800` exist ONLY in `@layer theme{:root,:host{...}}` (Tailwind's stock defaults) — yet those three account for 220 of the 303 weight utilities used (bold 124, semibold 84, extrabold 12).

**Impact.** Three font files are fetched and never painted. The fonts also arrive via a CSS `@import` at the top of a 130 KB stylesheet, which serialises the request chain: HTML → make.css → Google CSS → WOFF2 — two extra round-trips on the critical path with no `preconnect`. And the design system's own token set claims the brand uses only 400/500, while the UI is actually built on 600/700 — so the weights that carry the interface were never a design decision and cannot be changed from a token.

**Fix.** Request exactly the weights used: drop DM Sans 300 and both Playfair italic axes; add DM Sans 700. Promote 600 and 700 into `:root` as `--font-weight-semibold`/`--font-weight-bold` so they are real brand tokens. Replace the `@import` with `<link rel="preconnect">` + `<link rel="preload" as="font" crossorigin>` in the document head, or self-host.

**Magento.** Self-host under `web/fonts/` with `@font-face` + `font-display: swap` in `_typography.less` and preload via `layout/default_head_blocks.xml`. Google Fonts `@import` inside a Magento LESS file also breaks `setup:static-content:deploy` minification ordering in some setups — declare it in layout XML, not LESS.

### `forced-colors-mode-loses-all-focus` — In Windows High Contrast the 13 ring-based focus indicators disappear, because `outline-none` removes the outline and box-shadow rings are not painted

**Evidence.** The 13 form fields that do have a focus style use `"w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"`. Tailwind compiles the ring to a box-shadow — `.focus\:ring-2:focus{--tw-ring-shadow:var(--tw-ring-inset,)0 0 0 calc(2px + var(--tw-ring-offset-width))var(--tw-ring-color,currentcolor);box-shadow:…}` — and compiles the outline reset to `.focus\:outline-none:focus{--tw-outline-style:none;outline-style:none}`, with no transparent-outline fallback (that is what the separate `outline-hidden` utility provides: `.focus-visible\:outline-hidden:focus-visible{outline-offset:2px;outline:2px solid #0000}`).

**Impact.** `forced-colors: active` suppresses `box-shadow`. Since the outline has been set to `outline-style:none`, these fields end up with no focus indicator whatsoever in High Contrast mode. Separately, `focus:ring-blue-500` (#3b82f6) is off-brand and only reaches 3.68:1 on white and 2.50:1 against the field's own `border-gray-300` — barely at the 3:1 threshold against white and below it against the adjacent border.

**Fix.** Use `outline-hidden` instead of `outline-none` if a reset is unavoidable, or better, do not reset at all: use a real `outline` for the focus indicator so it survives forced-colors. Replace `ring-blue-500` with the brand `#0f2144` ring at 3px offset 2px.

### `free-shipping-threshold-conflict` — Three different free-shipping thresholds in two currencies across four components

**Evidence.** Header top bar: `"Free delivery on orders over AED 150 · Delivering across UAE, KSA, Qatar, Bahrain & Kuwait"`. PDP trust list: `text: "Free shipping on orders over $50"`. Cart logic: `s = l > 50 ? 0 : 9.99`. Bundle page: `["Free delivery over AED 150", "30-day returns", "Secure checkout"]`. Home hero: `"Same-day delivery · Free over AED 150"`.

**Impact.** A customer reading AED 150 in the header and $50 on the product page sees two different promises about the same rule; the cart then applies a third interpretation. This is a merchandising rule, so it will be configured once and every mismatched surface becomes a support ticket.

**Fix.** Pick one threshold in one currency, express it as a single token, and use that token in all five places. Replace every hardcoded `$` price literal with a currency-formatted value (35 `"$"` literals versus 9 `AED` mentions in the bundle).

**Magento.** Free shipping is one cart price rule with one threshold per website. If thresholds genuinely differ per country, that is one rule per website and the copy must be store-view-scoped — which the current static strings cannot express.

### `global-reset-collides-with-magento` — The stylesheet resets borders and restyles bare h1-h4 globally — it will re-skin Magento core markup it was never designed for

**Evidence.** make.css `@layer base` contains `*,:after,:before,::backdrop{box-sizing:border-box;border:0 solid;margin:0;padding:0}`, `*{border-color:var(--border);outline-color:var(--ring)}`, `img,svg,video,canvas,audio,iframe,embed,object{vertical-align:middle;display:block}`, `img,video{max-width:100%;height:auto}`, `ol,ul,menu{list-style:none}`, and element-level typography: `h1{font-size:var(--text-2xl);letter-spacing:-.01em;font-family:Playfair Display,Georgia,serif;font-weight:700;line-height:1.15}`, `h2{font-size:var(--text-xl);font-family:Playfair Display,Georgia,serif;font-weight:700;line-height:1.2}`, `h3{…font-weight:600…}`, `h4{font-size:var(--text-base);font-weight:var(--font-weight-medium)}`, `body{font-family:DM Sans,system-ui,sans-serif}`, `label,button{font-size:var(--text-base);font-weight:var(--font-weight-medium);line-height:1.5}`, `input{font-size:var(--text-base)…}`, `html{font-size:var(--font-size)}` where `--font-size:16px`.

**Impact.** Dropped into a Magento theme, `border:0 solid` strips borders from every core element the design never touched — checkout summary tables, address book cards, the admin-authored CMS blocks, Vnecoms seller dashboards. `list-style:none` flattens every merchandiser-authored ordered list in CMS content. Element-level `h1`-`h4` rules override headings inside CMS blocks and Page Builder content, so merchant-authored copy silently becomes Playfair — with no Arabic glyphs. `img{display:block}` breaks inline images in WYSIWYG content.

**Fix.** Ask Figma Make to scope the reset and the typographic defaults to the app root (a class or data attribute) rather than bare element selectors, and to express heading styles as classes/utilities that markup opts into, not as global `h1{}` rules.

**Magento.** This is the highest-risk item for the port. Magento storefronts render a large amount of markup the theme did not author — core checkout, customer account, Page Builder CMS content, Vnecoms seller pages, and third-party payment module UIs (Tabby/Tap). A global element-level reset plus global heading typography will silently restyle all of it. Scope the reset to `.page-wrapper` or a theme root class, and keep heading styles class-based, before any of this reaches `app/design/frontend/`.

### `icon-system-split` — Two parallel icon systems — 47 lucide icons and 38 emoji — with the same concepts in both

**Evidence.** 47 lucide icons are compiled (`= I("…")`): arrow-right, arrow-up-right, bell, calendar, chart-no-axes-column, chevron-down/left/right, circle-alert, circle-check-big, clock, credit-card, eye, globe, grid-3x3, heart, house, list, lock, map-pin, menu, message-circle, minus, package, phone, plus, refresh-cw, rotate-ccw, search, settings, share-2, shield, shopping-bag, shopping-cart, sliders-horizontal, smartphone, sparkles, star, store, tag, trash-2, trending-up, truck, user, users, x, zap. Alongside them, 38 distinct emoji are used as icons. The overlaps are functional, not decorative:
• star — lucide `star` (11 rating loops) + `"⭐ Top Vendors This Month"` (7935) + the literal glyph in `u.rating, " ★  ·  "` (8316) and `o.rating, " ★ • "` (8746)
• cart — lucide `shopping-cart` + lucide `shopping-bag` + `"🛒"` ×8 (category tiles, phone mockups, footer nav)
• check — lucide `circle-check-big` + `"✓ Verified"` (8008) and `"✓"` ×3 in the cart trust list (8946-8956)
• search — lucide `search` + `"🔍"` ×4 (all three no-results states)
• heart — lucide `heart` + `"♡"` in the phone mockup nav (8074)
• home/store — lucide `house` + lucide `store` + `"🏠"` + `"🏪 Featured Stores"`
• arrows — lucide `arrow-right`/`chevron-right` + literal `["←", "1", "2", "3", "→"]` in pagination (8566)
Section titles embed emoji directly in the heading string: `"🏷️ Today's Deals"`, `"🏆 Best Selling Items"`, `"🔥 Popular Products"`, `"🎁 Bundle Deals"`, `"🆕 New Stores on MECommerce"`.

**Impact.** Emoji render as the OS font — different glyphs and different colours on iOS, Android and Windows, so the "icon set" is uncontrollable and off-brand by definition. Emoji embedded in heading strings cannot be translated separately, so the Arabic heading either keeps a Latin-coded pictogram inline or loses it. The literal `★`/`←`/`→` glyphs will not mirror in RTL and are announced verbatim by screen readers.

**Fix.** Pick lucide as the single icon system. Replace every functional emoji (star, check, search, heart, cart, arrows) with its lucide equivalent. If emoji stay for the category tiles, treat them as illustration only, never as a control or a rating, and move them out of the heading strings into a separate `icon` field so copy can be translated independently.

**Magento.** Magento themes ship icons as an SVG sprite or an icon font referenced from LESS. One set means one sprite; a mixed set means the Arabic store view renders different glyphs than the English one on the same device.

### `language-choice-not-persisted` — The language/direction choice is component state only — no cookie, no localStorage, no URL — so it resets to English LTR on every reload and is not linkable

**Evidence.** Toggle state is `const [i, n] = z(!1)` (a `useState`) inside the layout component `oc`, and `g` writes `document.documentElement.dir` imperatively with no effect, no persistence and no cleanup. `localStorage` = 0 matches in make.js; `document.cookie` = 0 matches; the 3 `sessionStorage` hits are React Router's view-transition bookkeeping (`Failed to save applied view transitions in sessionStorage`), not language. No route or query parameter carries locale — the router config (make.js 457,058) is `path: "/"`, `"product/:id"`, `"category/:slug"`, … with no locale segment.

**Impact.** A shopper who switches to Arabic loses it on every navigation that causes a reload, cannot bookmark or share an Arabic URL, and search engines can never index the Arabic version because it has no address.

**Fix.** Give the locale a URL: prefix routes with `/:locale` (`/ar/category/grocery`) or use a distinct host. Persist the choice in a cookie so server-rendered pages get the right direction on first paint, and remove the imperative `document.documentElement.dir` write in favour of setting `dir`/`lang` from the route.

**Magento.** This is exactly Magento's store-view URL model — `/ar/` store code in the path or a separate domain, with `store` cookie persistence. Design the locale switcher as a store-view switcher (form POST to `stores/store/switch`) rather than a client-side toggle, and add `hreflang` alternates (`ar-AE`, `en-AE`, `x-default`) which are currently absent.

### `low-contrast-badges-and-overlays` — Discount badges, hero tile subtitles and image overlays fall between 1.70:1 and 3.76:1

**Evidence.** Discount badge `"absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm"` = 3.76:1 at 10px. Hero tile subtitles `"text-white/55 text-xs mt-0.5"` over the tile gradients: 4.56:1 on the navy tile (#1e3a6e), 2.75:1 on the green tile (#2d7a3a), 3.43:1 on the purple tile (#6b3fa0). Carousel arrow buttons `"w-9 h-9 bg-black/25 hover:bg-black/45 rounded-full ... text-white backdrop-blur-sm"` sit over arbitrary Unsplash photography — a 25%-black scrim is not enough to guarantee 3:1. The main hero overlay `overlay: "from-[#0f2144]/85 via-[#0f2144]/50 to-transparent"` reaches only 50% opacity behind the mid-length copy and 0% at the right edge, while carrying `text-white/80 text-sm` and a white h1. Vendor-card overlays `"absolute top-2.5 right-2.5 w-7 h-7 bg-black/30 backdrop-blur-sm ... text-white/60"` = 1.70:1.

**Impact.** Discount percentage is commercially critical text and fails 1.4.3 at 10px. Two of three hero tile subtitles fail. Text over photography with a partial gradient scrim is the classic uncontrollable-contrast pattern — a light product image behind the mid-gradient region drops the subtitle well under 4.5:1, and it will vary per merchandising upload. Fails 1.4.3 and 1.4.11.

**Fix.** Darken the discount badge from red-500 to `#c0392b` (`--destructive`, 5.44:1 with white). Raise hero-tile subtitles from `text-white/55` to `text-white/80` and darken the green and purple gradient stops. For text over photography, replace the directional gradient with a solid or near-solid scrim behind the text column (`from-[#0f2144]/90 via-[#0f2144]/80 to-[#0f2144]/40`) or place the copy on a solid panel. Raise the carousel-arrow scrim from `bg-black/25` to `bg-black/60`.

**Magento.** Merchandisers will upload their own banner images through CMS blocks, so a fixed scrim floor must be enforced in the theme CSS rather than left to the image. Bake it into the banner widget template.

### `micro-typography` — 117 uses of sub-12px type, down to 7px, on a storefront that must also render Arabic

**Evidence.** Arbitrary font sizes below the Tailwind scale: `text-[10px]` ×55, `text-[9px]` ×37, `text-[11px]` ×18, `text-[8px]` ×4, `text-[7px]` ×3 — 117 total. These are not all decorative: `text-[10px]` carries the vendor name on the default product card (`"text-[10px] text-[#f26522] font-semibold mb-1 truncate"`, 5842), the review count (`"text-[10px] text-gray-400 ml-0.5"`, 5847), the header utility captions (`"text-[9px] tracking-wide"` for Account/Wishlist/Cart, 5657/5661/5667), the cart-count badge (`"text-[9px] font-bold"`, 5666), the free-delivery promise, the payment-method chips (`"text-[10px] font-medium px-2 py-0.5 bg-white/10 …"`, 5744) and every footer link heading. `text-[9px]` is also combined with `tracking-widest uppercase` in ~20 places, which is the least legible possible combination. Truncation compounds it: `line-clamp-2` ×13, `line-clamp-1` ×3, `truncate` ×7 — all applied to the smallest sizes.

**Impact.** 9-10px Latin is already at the legibility floor; Arabic script needs roughly 2px more than Latin for equivalent x-height legibility, so these strings will be effectively unreadable in the Arabic store view. Vendor name, review count and cart quantity — three pieces of information a marketplace shopper actually needs — are among the smallest text on the page.

**Fix.** Set a 12px floor for any text that carries information, reserving 10px strictly for decorative eyebrows, and specify a separate (larger) size ramp for the RTL store view. Promote vendor name, review count and cart badge to at least 12px. Replace the 117 arbitrary sizes with a named ramp (`text-2xs`/`text-xs`/`text-sm`…) so the Arabic override is one token change.

**Magento.** Magento switches the store view's stylesheet, so an `[lang=ar]` / RTL type ramp is straightforward — but only if the sizes are tokens. 117 arbitrary values would each need overriding by hand.

### `missing-landmarks-and-skip-link` — No skip link, the desktop category bar is not a `<nav>`, and the breadcrumb is a div with literal "/" spans

**Evidence.** `/Skip to|skip-link|skipnav/i.test(make.js)` = false. Element census: `nav` × 1 (the mobile menu only), `main` × 1, `header` × 1, `footer` × 1, `aside` × 2. The desktop category bar is `r("div", { className: "flex items-center flex-wrap", children: [ta.map((p) => r(C, { to: p.href, ... }))] })` inside a plain `<div className="bg-[#162d5a] border-t border-white/8">`. The Category breadcrumb is `r("div", { className: "flex items-center gap-2 text-xs text-white/50 mb-3", children: [t(C,{to:"/",children:"Home"}), t("span",{children:"/"}), t("span",{className:"text-white/80",children:T})] })`. The header itself is `"bg-[#0f2144] sticky top-0 z-50 shadow-lg"` — four stacked bands, sticky, roughly 190px.

**Impact.** A keyboard user must tab through the utility bar, logo, search, three account links and ten category links on every single page load before reaching content — fails WCAG 2.4.1 Bypass Blocks. The 10-item category bar has no landmark and no accessible name, so screen-reader landmark navigation skips it. The breadcrumb separator is announced as "slash". The sticky ~190px header also eats most of the viewport at 400% zoom, threatening 1.4.10 Reflow.

**Fix.** Add a visually-hidden-until-focused skip link as the first focusable element (`href="#maincontent"`). Wrap the desktop category bar in `<nav aria-label="Product categories">` and the footer link columns in `<nav aria-label="Footer">`. Rebuild the breadcrumb as `<nav aria-label="Breadcrumb"><ol>…</ol></nav>` with the separator moved to CSS `::before` content. Collapse the sticky header to a single compact band below the `md` breakpoint.

**Magento.** Magento's `Magento_Theme::html/notices.phtml` region and the `page.main.title` block give you `<main id="maincontent">` for free, and `Magento_Catalog` already renders breadcrumbs as an `<ol>` in a labelled nav. Use the core breadcrumb block rather than re-implementing it.

### `no-sku-no-specs` — The product model has no SKU, brand or attribute data, so the PDP has no specification section

**Evidence.** `grep -ci 'sku' make.js` = 0. The product model (make.js:6056+) is `{ id, name, price, originalPrice, image, vendorId, vendorName, category, rating, reviews, description, inStock, variants }` — no sku, no brand/manufacturer, no weight, no attribute list, no url_key, no stock qty.

**Impact.** No SKU is displayed anywhere (Magento shows it on the PDP by default and customers in the Gulf routinely quote it to WhatsApp support), and there is no 'More Information' / specifications table, so richly attributed categories like Electronics and Pharmacy have nowhere to show model number, dimensions, ingredients or expiry.

**Fix.** Add SKU to the PDP buy box, and design a specifications table (label/value rows) for the PDP. Add brand to the card and PDP if brand pages are planned — the home page already shows an `"All Brands "` link with no brand page behind it.

**Magento.** Attribute-set-driven spec tables are free in Magento (`product.attributes` block) — the design just has no slot for them. Note the project's standing constraint: free-text attributes like model_num must never be set filterable_in_search or OpenSearch breaks storefront-wide.

### `no-tabular-numerals-anywhere` — `font-variant-numeric` is never applied, so money columns in cart and checkout will not align on the decimal

**Evidence.** `tabular-nums` appears 0 times in make.js (the utility `.tabular-nums{--tw-numeric-spacing:tabular-nums;font-variant-numeric:...}` is compiled into make.css but never referenced). Order-summary rows are laid out as `r("div", { className: "flex justify-between", ...)`, `"flex justify-between text-sm"`, `"flex justify-between text-lg font-bold mb-6"`, `"flex justify-between text-lg font-bold pt-4"`.

**Impact.** DM Sans and Playfair Display both default to proportional figures. In a `flex justify-between` money column, `$1,199.00` and `$89.00` are right-aligned to the box but their digits do not share widths, so the decimal points and thousands separators wander line to line down the Order Summary. Ratings counts and quantity steppers reflow on change for the same reason.

**Fix.** Apply `font-variant-numeric: tabular-nums` to every price, subtotal, total, tax line, quantity, rating value and review count — cleanest as a `.numeric` class or directly on the price component.

**Magento.** Add it once to `.price`, `.qty`, `.rating-result` and the `.table-totals td` selectors in `_typography.less`; it inherits into Vnecoms vendor commission tables too.

### `orphan-and-duplicate-tokens` — 16 of the 33 declared tokens describe components that do not exist; 10 more are exact duplicates of each other

**Evidence.** ORPHANS (no component in the design): the 8 sidebar tokens `--sidebar:#f6f4f1; --sidebar-foreground:#0a0a0a; --sidebar-primary:#0a0a0a; --sidebar-primary-foreground:#fff; --sidebar-accent:#ede9e3; --sidebar-accent-foreground:#0a0a0a; --sidebar-border:#00000017; --sidebar-ring:#0003` (there is no sidebar; `sidebar` appears 0 times in make.js); the 5 chart tokens `--chart-1..--chart-5` (no charts; the recharts CSS hooks `[&_.recharts-cartesian-grid_line[stroke='#ccc']]` etc. — the only source of the 8 `#ccc` occurrences — are all dead); `--switch-background:#c9c4bc` (no switch component); `--input:transparent` and `--input-background:#f6f4f1` (0 uses of `bg-input-background`; #f6f4f1 is instead used as a page surface via `bg-[#f6f4f1]` ×20). `--sidebar-accent:#ede9e3` is the only place #ede9e3 exists in the entire build. DUPLICATES: `--background`, `--card`, `--popover`, `--primary-foreground`, `--accent-foreground`, `--destructive-foreground` are all `#fff` (6 names, 1 value); `--foreground`, `--card-foreground`, `--popover-foreground`, `--secondary-foreground` are all `#1a1a2e` (4 names, 1 value); `--muted` == `--secondary` == `#f5f7fa`; `--border` == `--sidebar-border` == `#00000017`; `--ring` == `--sidebar-ring` == `#0003`.

**Impact.** The token file looks like a real design system but roughly half of it is boilerplate the design never used, and another third is aliasing that hides which surfaces are actually independent. It misleads whoever maps it into LESS and makes 'is this token used?' unanswerable without grepping.

**Fix.** Delete the 8 sidebar tokens, 5 chart tokens, `--switch-background`, `--input`. Keep aliases only where the design genuinely intends them to be able to diverge (card vs background, for example) and document that intent; otherwise collapse.

**Magento.** A 1:1 port of this token list into `_theme.less` would create 16 dead LESS variables on day one. Prune before mapping.

### `pdp-gallery-and-dead-controls` — The PDP gallery is the same image three times, and several controls are decorative

**Evidence.** Product page: `p = [a.image, a.image, a.image]` (8090) — the gallery and its three thumbnails render one image repeated, with the thumbnail buttons carrying `alt: ""` (8127). No zoom, no video, no per-variant imagery even though products declare colour variants (`{ value: "rose-gold", label: "Rose Gold", inStock: !1 }`, 6119).
Decorative controls that look functional: the VendorProfile sort `r("select", { className: "border rounded-lg px-4 py-2", children: [t("option", { children: "Featured" }), … ] })` (9415) and the order-history filter (9623) have no `value` and no `onChange`. The header search never passes its query: `u = (p) => { p.preventDefault(), m("/search") }` (5596) discards the `l` state, so submitting the site search lands on an empty Search page. The Search page's Vendors and Categories tabs ignore the query entirely — `children: at.map(…)` (8720) and `children: Aa.map(…)` (8760) — while only the Products tab shows a count (`"Products (", l.length, ")"` vs bare `children: "Vendors"`). Add to cart on the PDP is `T = () => { R && (m(!0), setTimeout(() => m(!1), 2e3)) }` (8100) — a 2-second flag with no cart mutation, and the header badge is the literal string `"3"` (5666) on every page.

**Impact.** The PDP has no gallery specification at all, which is the component with the highest conversion impact on the page. Three controls are drawn as if they work and do not, so they cannot be reviewed or signed off. The site search — the header's largest element — is designed as a dead end.

**Fix.** Specify a real gallery: main image + thumbnail strip with distinct images, per-variant image switching, zoom behaviour, and mobile swipe. Wire the header search to pass its query. Make the Search vendor/category tabs filter by the query and show counts. Either implement the two sort selects or remove them from the design.

**Magento.** Magento's `Magento_Catalog` gallery (Fotorama) with swatch-driven image switching is core functionality that the theme styles rather than rebuilds — but the design has to say what it should look like at each breakpoint, including the thumbnail strip position, which is currently unspecified because there is only one image. Vnecoms search results are also split by seller; the vendor tab needs a real query contract.

### `price-strikethrough-has-no-semantics` — Was-prices are conveyed by `line-through` styling alone, at 2.54:1, with no `<del>` and no text cue

**Evidence.** ProductCard default variant: `r("span", { className: "text-xs text-gray-400 line-through", children: ["$", e.originalPrice] })` beside `r("span", { className: "text-lg font-extrabold text-[#0f2144]", children: ["$", e.price] })`. Compact variant uses `"text-[9px] text-gray-400 line-through ml-1"`. Cart uses `r("div", { className: "text-sm text-gray-500 line-through", children: ["$", p.originalPrice] })`. PDP uses `"text-lg text-[#9e9890] line-through"`. The AI band uses `"text-[10px] text-white/30 line-through ml-1.5"` = 2.64:1.

**Impact.** CSS `text-decoration: line-through` on a `<span>` is not exposed to the accessibility tree by most screen readers, so a user hears "$299 $399" with no indication which is current — a materially misleading price presentation, and a consumer-protection risk in the UAE/KSA. Fails 1.3.1 and, because strikethrough plus grey colour is the only differentiator, 1.4.1.

**Fix.** Use `<del>` (or add visually-hidden "Was" / "Now" text) so the relationship is programmatic: `<span class="sr-only">Now</span>$299 <del><span class="sr-only">was</span>$399</del>`. Raise the was-price colour from gray-400 to gray-600 (7.56:1).

**Magento.** Magento's `final_price`/`old_price` price render templates already emit `<span class="price-label">Regular Price</span>`. Keep those label spans and hide them visually with `.lib-visually-hidden()` rather than deleting them from the override template.

### `ring-token-dead-focus-invisible` — --ring:#0003 (1.61:1) is the only focus indicator the token layer offers, and three inputs remove their outline with no replacement at all

**Evidence.** `--ring:#0003` = rgba(0,0,0,0.2); blended on white that is #cccccc, contrast **1.61:1** — below the 3:1 required by WCAG 2.2 SC 1.4.11 for a focus indicator. Its only consumer is the base rule `*{border-color:var(--border);outline-color:var(--ring)}`; `.focus\:ring-ring{--tw-ring-color:var(--ring)}` and `.focus-visible\:outline-ring` are in the dead set. In the app: `focus:outline-none` ×20, `focus-visible` ×0, `focus:ring-2 focus:ring-blue-500` ×13, `focus:border-[#0a0a0a]` ×3, `focus:border-[#c85c2c]` ×1, `focus:border-[#f26522]` ×1, `focus:border-red-400` ×1. Three elements strip the outline with nothing replacing it: `"bg-white/10 text-white/80 text-xs px-3 py-2.5 border-r border-white/15 focus:outline-none cursor-pointer min-w-[130px] appearance-none"` (header category select), `"flex-1 bg-white text-[#1a1a2e] text-sm px-4 py-2.5 focus:outline-none placeholder:text-gray-400 min-w-0"` (header search input), `"flex-1 bg-white text-sm px-3 py-2.5 focus:outline-none"` (mobile search input).

**Impact.** Keyboard users lose the focus indicator entirely on the header search — the most-used control on the site — and the system-level ring token, if ever wired up, would be invisible at 1.61:1. The 13 `focus:ring-blue-500` rings are also yet another blue (#2b7fff) unrelated to the brand.

**Fix.** Set `--ring` to a brand colour at ≥3:1 against both white and the navy header (e.g. #c2410c or #0f2144 depending on ground), apply it via `focus-visible` on every interactive element, and never use bare `focus:outline-none` without a replacement. Replace `focus:ring-blue-500` with the ring token.

**Magento.** Magento's `_forms.less` sets `@focus__box-shadow`; map the ring token there once. The header search is `Magento_Search::form.mini.phtml` — verify keyboard focus there specifically after theming, in both LTR and RTL.

### `search-tabs-are-not-tabs` — Search result tabs have no tab semantics, no keyboard model, and the search field steals focus on load

**Evidence.** make.js Search page: three sibling buttons, e.g. `t("button", { onClick: () => n("products"), className: `pb-2 px-1 ${i === "products" ? "border-b-2 border-blue-600 text-blue-600 font-medium" : "text-gray-600"}` , children: ["Products (", l.length, ")"] })` — no `role="tablist"`, `role="tab"`, `aria-selected`, `aria-controls` or `role="tabpanel"` (all `role=` counts are 0). The search input carries `autoFocus: !0`.

**Impact.** A screen-reader user is not told these three buttons form a group, which one is selected, or that activating one swaps the panel below. Arrow-key navigation between tabs does not exist. Separately, `autoFocus` moves focus past the header on every load, so a screen-reader user never hears the page title or landmarks and a keyboard user cannot reach the header without Shift+Tab.

**Fix.** Either add full tab semantics (`role="tablist"` / `role="tab"` + `aria-selected` + `aria-controls` + roving tabindex with Left/Right arrow handling), or — simpler and equally valid — leave them as buttons and add `aria-pressed` plus a live-region announcement of the new result count. Remove `autoFocus`.

### `shadow-and-radius-tokens-detached` — All shadows use Tailwind's default black tints (no shadow token), and rounded-xl and rounded-2xl resolve to the identical 1rem

**Evidence.** SHADOWS: `.shadow-sm{--tw-shadow:0 1px 3px 0 var(--tw-shadow-color,#0000001a),0 1px 2px -1px var(--tw-shadow-color,#0000001a)}`, `.shadow-md{…#0000001a…}`, `.shadow-lg{…#0000001a…}`, `.shadow-xl{…#0000001a…}`, `.shadow-2xl{--tw-shadow:0 25px 50px -12px var(--tw-shadow-color,#00000040)}`, `.shadow-xs{…#0000000d}`, `--drop-shadow-md:0 3px 3px #0000001f`. Every shadow colour is stock Tailwind black at 5/10/25% — no `--shadow` token exists. Exactly one brand-tinted shadow in the whole design: `.shadow-\[\#f26522\]\/20{--tw-shadow-color:#f2652233}` used once, on the Bundles add-to-cart. Usage: shadow-md ×13, shadow-lg ×9, shadow-sm ×7, shadow-xl ×2, shadow-2xl ×1. RADIUS: `--radius:.75rem`; `.rounded-xl{border-radius:calc(var(--radius) + 4px)}` = 1rem, but `.rounded-2xl{border-radius:var(--radius-2xl)}` with `--radius-2xl:1rem` — **the same 1rem**, and not derived from `--radius`. Usage: rounded-lg ×112, rounded-full ×63, rounded-xl ×27, rounded-2xl ×8, rounded-md ×1, rounded-sm/xs/none ×0. Also `.rounded{border-radius:.25rem}` is hardcoded outside the scale.

**Impact.** Elevation is untokenised, so shadow depth cannot be tuned per brand or flattened for the Arabic/print/AMP contexts. Two different radius names produce identical geometry, which means designers and developers will disagree about which to use and a future change to `--radius` will desync them (rounded-xl would move, rounded-2xl would not).

**Fix.** Add `--shadow-color` (a navy-tinted black, e.g. rgba(15,33,68,.10)) and 3 elevation tokens; replace all Tailwind shadow defaults. Derive `--radius-2xl` from `--radius` (`calc(var(--radius) + 8px)`) or delete `rounded-2xl` and standardise on lg/xl/full. Remove the bare `.rounded`.

**Magento.** Maps to `@button__border-radius` / `@form-element-input__border-radius` and a shadow mixin in `_theme.less`. Low effort, but do it while the token list is being pruned rather than after templates are written.

### `system-mono-leaks-in` — A third, OS-dependent typeface (system monospace) appears above the fold on Home for the deals countdown

**Evidence.** `r("span", { className: "text-xs font-mono font-bold tracking-wider", children: [ String(i.h).padStart(2, "0"), ":", String(i.m).padStart(2, "0"), ":", String(i.s).padStart(2, "0") ] })` — the only `font-mono` in the bundle. `.font-mono{font-family:var(--font-mono)}` with `--font-mono:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace`.

**Impact.** The 'Today's Deals' countdown in the Home band renders in Menlo on macOS, Consolas on Windows and Courier New elsewhere — an unspecified typeface in the brand's most visible section. It also carries `font-bold` on a system mono that often has no bold face, and 0.05em tracking that widens an already-wide face.

**Fix.** Remove `font-mono`. Render the countdown in DM Sans with `font-variant-numeric: tabular-nums` — that solves the digit-jitter problem `font-mono` was there to solve, without importing a third family.

**Magento.** If a mono is genuinely wanted anywhere, declare one self-hosted face; never ship a system-stack fallback into a branded storefront.

### `third-party-brand-colours-hardcoded` — Eight third-party trademark colours are hardcoded in a 'Shop by Brand' strip

**Evidence.** Line ~7920: `[{name:"Samsung",emoji:"🇰🇷",color:"#1428a0"},{name:"Apple",emoji:"🍎",color:"#555"},{name:"Philips",emoji:"💡",color:"#0b5ed7"},{name:"LG",emoji:"📺",color:"#a50034"},{name:"De'Longhi",emoji:"☕",color:"#c41e3a"},{name:"Roborock",emoji:"🤖",color:"#e31e24"},{name:"Alienware",emoji:"👾",color:"#00d1ff"},{name:"Anker",emoji:"🔌",color:"#0080ff"}]` rendered with `style:{backgroundColor: f.color + "15"}` (7922). #1428a0 and #a50034 are Samsung's and LG's registered brand colours. `#555` is a 3-digit shorthand — the only one in the JS bundle. Brands are represented by unrelated emoji (a Korean flag for Samsung, a TV for LG, a robot for Roborock).

**Impact.** Eight more off-palette colours, plus trademark and representation risk: using a manufacturer's brand colour and a stand-in emoji instead of their logo is both legally uncomfortable and visually poor. It also blocks the strip from ever being merchant-managed.

**Fix.** Replace with real brand logo assets on a neutral token tint, or drop the colour entirely and use a uniform `--muted` chip. Remove the emoji stand-ins.

**Magento.** In Magento this is a brand/manufacturer attribute with an image — model it as such (attribute + image + URL) rather than a hardcoded array, so merchandising can maintain it and the legal team can control which logos appear.

### `tracking-uppercase-and-tiny-type-hostile-to-arabic` — 52 letter-spacing utilities, 42 `uppercase`, 4 `leading-none` and 117 sub-12px sizes — all of which degrade or are meaningless in Arabic

**Evidence.** `tracking-widest`×42, `tracking-wider`×4, `tracking-wide`×4, `tracking-tight`×2 (52 total). `uppercase`×42, always paired with tracking, e.g. `"text-[10px] tracking-widest uppercase text-[#9e9890]"` (Vendors 399,537), `"text-xs tracking-widest uppercase text-white bg-[#c85c2c] px-2 py-1"` (Product Save badge), `"text-[10px] text-white/35 uppercase tracking-widest mb-2"` (footer "Accepted Payments").
Font sizes below 12px: `text-[7px]`×3, `text-[8px]`×4, `text-[9px]`×37, `text-[10px]`×55, `text-[11px]`×18 = 117 instances.
Line heights: `leading-none`×4, `leading-tight`×11.
Fixed text-box heights: `min-h-[2rem]` and `min-h-[2.5rem]` on `line-clamp-2` product titles; `whitespace-nowrap` ×3 on the category nav; `min-w-[130px]` on the header category select.
One `capitalize` on the Product breadcrumb: `className: "hover:text-[#c85c2c] transition-colors capitalize", children: a.category.replace(/-/g, " ")`.

**Impact.** `letter-spacing` on Arabic separates cursively-joined letterforms and is regarded as an error in Arabic typography; `text-transform: uppercase` and `capitalize` are no-ops (Arabic is unicameral), so 42 labels lose the visual hierarchy they carry in English with no substitute. Arabic requires ~1.4–1.6 line-height for ascenders/descenders and diacritics — `leading-none`/`leading-tight` will clip. At 7–10px Arabic is effectively unreadable, and the fixed `min-h-[2rem]` title boxes plus `whitespace-nowrap` nav items will clip or overflow with longer Arabic labels.

**Fix.** Gate the typographic treatments on direction: `ltr:tracking-widest ltr:uppercase` so Arabic gets neither, and replace the lost hierarchy in Arabic with weight/colour/size instead. Raise the minimum body size to 12px (14px preferred) for the Arabic view, set `leading-relaxed` as the Arabic default, replace `min-h-[2rem]` with `min-height` in `em` or remove it, and drop `whitespace-nowrap` from the category strip so Arabic labels can wrap.

**Magento.** Put these as `[dir=rtl]` overrides in the theme LESS rather than duplicating components, and re-check the category nav at 360px width with real Arabic labels — the 10-item strip is already at the edge of its space in English.

### `vendor-fields-not-in-vnecoms` — Vendor cards depend on five data points Vnecoms does not have out of the box

**Evidence.** Vendor model (make.js:6000-6042): `rating: 4.8`, `verified: !0`, `joinedDate: "2023-01-15"`, `totalProducts: 156`. Vendor page adds hardcoded `children: "98%"` / `"Positive Reviews"` and `children: "24h"` / `"Response Time"` (make.js:9376-9388). Directory offers a `"Verified Only"` toggle and sorts by `"top-rated"` / `"most-products"` / `"newest"`.

**Impact.** Vendor rating, verified status, positive-review percentage and response time are surfaced as trust signals across the PDP, directory, home page and vendor page — but none exist as fields, so at launch they would be either blank or fabricated. Fabricated trust badges on a marketplace are a real liability.

**Fix.** For each: decide the source or cut the element. Verified -> a vendor attribute set by admin on KYC approval (cheap, do it). Rating -> requires vendor reviews to exist and aggregate. Response time and 'positive reviews %' -> require message-thread timestamps and review sentiment; recommend cutting both.

**Magento.** 'Verified' is the cheapest and highest-value one; it is a boolean on the vendor entity plus an admin toggle. The other three need data pipelines that do not exist in the current stack.
