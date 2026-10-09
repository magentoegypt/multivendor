# Hub Market: website vs Customer App gap analysis

**Date:** 2026-10-09

**Website:** `magentoegypt/multivendor`, branch `figma-parity-home` at `bb8d463f9`. This is the live branch. `main` does not have the HubApp, AlgoliaVendor, Tabby or Tamara code.

**Customer App:** `magentoegypt/hubmarket-app` (Flutter, GraphQL only, plus one REST pair for WhatsApp OTP), default branch.

**Method:** static review of both codebases. The app's operation validator was run offline: 127 operations and 26 fragments, 0 problems against the committed schema plus the HubApp contract. A live probe of `hmAppConfig` was not possible from the review environment, so every "live" statement below comes from the repos' own docs and should be confirmed on the server (see P0-1).

---

## 1. Summary

The app already covers the core shopping journey and most of the marketplace features:

- catalogue, Algolia search, PDP with "sold by" and other sellers' offers
- stores
- cart grouped by store
- 3-step checkout
- orders with per-store packages, cancel, reorder and guest lookup
- returns / RMA
- store credit
- reviews
- wishlist
- brands, deals, bundle deals, best sellers
- a CMS-driven home page (`hmAppHome`)

Most of this is possible because the website was given a purpose-built GraphQL layer, the **HubApp module family** (`MagentoEgypt_HubApp*`).

The real gaps fall into four groups:

1. **Revenue blockers.** The app can only take **offline payments** (COD). Card payments (Paymob/Accept), Tabby and Tamara exist on the website but have no GraphQL / mobile flow. The installed Paymob module is also hard-wired to the **Egyptian** endpoint while the store sells in AED.
2. **Engagement blockers.** Push notifications and deep links are built in the app but not switched on:
   - Firebase secrets are missing.
   - The `assetlinks.json` and `apple-app-site-association` files are missing.
   - The backend only sends pushes an admin schedules by hand. Nothing is sent when an order changes status.
3. **Website features with no API.**
   - Seller (vendor) coupons
   - Lof review extras: photos, likes, verified purchase, seller reply
   - Amasty delivery date and gift wrap
   - Guest returns
   - Invoice / PDF documents
4. **Backend configuration and hygiene**, which make the app look broken even though it is not:
   - order cancellation disabled
   - UAE postcode required
   - missing CMS blocks
   - Egypt text in the test catalogue
   - stale committed schema
   - **credentials and a database dump committed to a public repo**

Several "missing" items are **not gaps**, because the website does not have them either: vendor chat, follow-a-store, social login, reward points, gift cards, blog and store locator (the MGS modules are decommissioned), and size guide / "frequently bought together". They are listed in §6 so they are not built by mistake.

---

## 2. Feature matrix (website vs app)

Legend: ✅ done · 🟡 partial / gated · ❌ missing · ➖ not on the website either

| # | Feature | Website (module) | API exposed | App | Gap owner |
|---|---|---|---|---|---|
| 1 | Home page sections | HeroBanner, HomeSections, CMS blocks | `hmAppHome` (17 section types) | ✅ | — |
| 2 | Category tree / PLP / layered navigation | Mageplaza LayeredNavigation (allow-list) | core `products`, aggregations | ✅ | — |
| 3 | Search (Algolia, landing page, trending) | Algolia, AlgoliaVendor, SearchLanding | Algolia direct; `hmAppConfig.algolia`, `search.trending_terms` | ✅ (key falls back to scraping storefront HTML) | Backend (config) |
| 4 | PDP, configurable products | core | core | ✅ | — |
| 5 | Product **custom options** | core | core `CustomizableOption` | ❌ not supported | App |
| 6 | Bundle products / Bundle Deals | BundleExtend | `hmBundleQuote`, `hmAddBundleToCart`, `hmBundleDeals` | ✅ (Build 1 falls back to the website) | — |
| 7 | Sold by / other sellers' offers | Vnecoms PriceComparison | `hm_seller`, `hm_other_offers` | ✅ | — |
| 8 | Store list / store page / store reviews | VendorsPage, SellerList | `hmStores`, `hmStore`, `hmStoreReviews`, `hmStoreCategories` | ✅ | — |
| 9 | Today's Deals / Best Sellers / Picked for you / Brands | HomeSections, Dailydeals, MGS_Brand | `hmDeals`, `hmBestSellers`, `hmPickedForYou`, `hmBrands` | ✅ | — |
| 10 | Product reviews (basic) | Magento_Review + Lof_ProductReviews | core `createProductReview`, `customer.reviews` | ✅ | — |
| 11 | Review photos, likes, report, verified purchase, seller reply | Lof_ProductReviews | **REST only** (`/V1/reviews/me/*`, `/V1/products/:sku/reviews`) | ❌ (hidden) | Both |
| 12 | Product compare | core (list button kept on the website) | core `compareList` | ❌ | App (low) |
| 13 | Wishlist | core | core | ✅ customer only | — |
| 14 | Guest wishlist | MGS_Guestwishlist (**disabled**) | — | ➖ | — |
| 15 | Cart, cart-level coupon, merge carts | core | core | ✅ | — |
| 16 | **Seller (vendor) coupons** | Vnecoms Coupon | **REST only** `/V1/carts/mine/vendor-coupons`, `/V1/guest-carts/:id/vendor-coupons` | ❌ | Both |
| 17 | Store credit (balance, top-up, apply) | Vnecoms Credit | `hmStoreCredit`, `hmApplyStoreCredit`, … | ✅ (flag `store_credit`) | — |
| 18 | Checkout: address, shipping, offline payment | Amasty OSC + core | core | ✅ | — |
| 19 | **Card payments (Paymob/Accept)** | Accept_Payments (iframe, **Egypt endpoint**) | none (iframe/redirect, no GraphQL) | ❌ | Both |
| 20 | **Tabby / Tamara BNPL** | `tabby/m2-checkout`, `tamara-solution/magento` | REST only (Tabby session-data); no GraphQL | ❌ | Both |
| 21 | Saved cards (vault) | core vault | `customerPaymentTokens`, `deletePaymentToken` | 🟡 list and delete only | Depends on #19 |
| 22 | Delivery date, gift wrap | Amasty CheckoutDeliveryDate / GiftWrap | **REST only** `/V1/amasty_checkout/...` | ❌ | Both |
| 23 | Gift message | Amasty / core | core `setGiftOptionsOnCart` | ❌ | App |
| 24 | Checkout OTP (guest) | Vnecoms SMS | `customerCheckoutSendOtp` / `customerCheckoutVerifyOtp` | 🟡 built, flag off (`guestCheckoutOtp: false`) | Product decision |
| 25 | Free-shipping threshold bar | Amasty / Vnecoms rates | `hmAppConfig.shipping` | 🟡 shown only if a threshold is published | Backend (config) |
| 26 | Orders, order detail, per-store packages, tracking | core + HubAppOrders | `customer.orders`, `hm_packages` | ✅ | — |
| 27 | Cancel order | OrderCancellationUi | core `cancelOrder` | 🟡 built, **disabled** (`order_cancellation_enabled=false`) | Backend (config) |
| 28 | Reorder | core | core `reorderItems` | ✅ client-side re-add (does not use `reorderItems`) | App (minor) |
| 29 | **Invoices / shipments / credit memos, print/PDF** | core print layouts (incl. guest) | GraphQL has the data, **no PDF endpoint** | ❌ (hidden) | Both |
| 30 | Returns / RMA (customer) | Vnecoms RMA | `hmReturns`, `hmCreateReturn`, … | ✅ (flag `returns`) | — |
| 31 | **Returns for guests** | Vnecoms RMA (guest) | **none** in HubAppReturns | ❌ | Both |
| 32 | Sign-in email/password, register, forgot/reset | core + Vnecoms SMS | core + OTP mutations | ✅ | — |
| 33 | WhatsApp OTP sign-in | SmsExtend | REST `/V1/whatsapp/otp/*` and `hmSendWhatsAppCode` / `hmSignInWithWhatsAppCode` | ✅ | — |
| 34 | Social login | **none on the website** | legacy Mstore REST only | ➖ | — |
| 35 | Address book (UAE emirates, labels) | core | core | ✅ (postcode currently required) | Backend (config) |
| 36 | Location picker / map | theme folder `MagentoEgypt_DeliveryAvailability` (**no module**) | — | ❌ | Both (and the website itself) |
| 37 | Profile, mobile number, password | core + SMS | core + `saveMobileToCustomer` | ✅ | — |
| 38 | Change email | core | core `updateCustomerEmail` | ❌ | App |
| 39 | Delete account / GDPR | MGS_GDPR | core `deleteCustomer` | ✅ | — |
| 40 | Newsletter | core | core | ✅ customer; ❌ guest sign-up | App (low) |
| 41 | SMS notification preferences | Vnecoms SMS (`customer/account/sms`) | — | ❌ | Both (low) |
| 42 | Contact us / FAQ / CMS pages | core | `contactUs`, `cmsBlocks` (`hm_app_faq` **missing**) | ✅ | Backend (content) |
| 43 | **Push notifications** | PushNotification (admin-scheduled FCM only) | `hmRegisterDevice` / `hmUnregisterDevice` | 🟡 dormant (no Firebase config) | Both |
| 44 | Notification inbox | — | — | 🟡 device-local only | Backend (optional) |
| 45 | **Deep links / universal links** | — | needs `/.well-known/*` files | 🟡 custom scheme only | Both |
| 46 | Become a seller | Vnecoms "Marketplace" form | `createVendor` | 🟡 WebView | — (acceptable) |
| 47 | Request a quote | Vnecoms Quotation (PDP button **removed**) | admin REST only | ➖ | — |
| 48 | Blog / store locator / lookbook | MGS (**decommissioned**) | — | ➖ (WebView for leftover links) | — |
| 49 | Maintenance mode / forced update | HubApp config | `hmAppConfig` | ✅ | — |
| 50 | Analytics / crash reporting | GA / Algolia Insights on the web | Algolia Insights | 🟡 Insights only; no Crashlytics/Analytics | App |

---

## 3. Answers to the five questions

### 3.1 Modules and features on the website but missing from the app

| Feature | Website module | Why it is missing |
|---|---|---|
| Card payments | `Accept_Payments` (Paymob) | iframe/redirect only, no GraphQL; Egyptian endpoint |
| Tabby / Tamara | `tabby/m2-checkout`, `tamara-solution/magento` | REST / web-checkout only |
| Seller coupons | `Vnecoms_Coupon` / VendorsCoupon | REST only |
| Review photos, likes, report, verified badge, seller replies | `Lof_ProductReviews` | REST only |
| Delivery date, gift wrap | Amasty CheckoutDeliveryDate / GiftWrap | REST only |
| Gift message | core / Amasty | GraphQL exists, app not wired |
| Invoice / shipment / credit-memo documents and print | core | no PDF API; app does not render invoices |
| Guest returns | `Vnecoms_RMA` | not in HubAppReturns |
| Product compare | core | app not wired (low value on mobile) |
| SMS preference page | Vnecoms SMS | no API |
| Change email | core | app not wired |
| Guest newsletter sign-up | core | app not wired |
| Product custom options | core | app does not render `CustomizableOption` |

### 3.2 Magento modules present but not integrated into the app

| Module | API it already offers | Integrated? |
|---|---|---|
| Vnecoms seller GraphQL (`vendor*`, `v_*`, `createVendor*`) | GraphQL | No. This is **correct**: it is the seller API (belongs in the vendor app). |
| `Vnecoms_Coupon` (seller coupons) | REST | No |
| `Vnecoms_Quotation` | admin REST | No (also hidden on the website) |
| `Vnecoms_Sms` customer preferences | — | No |
| `Lof_ProductReviews` extras | REST | No |
| Amasty One Step Checkout extras (delivery date, gift wrap, custom fields) | REST | No |
| `Accept_Payments`, Tabby, Tamara | REST / redirect | No |
| `Magento_GiftMessageGraphQl` | GraphQL | No |
| `Magento_CompareListGraphQl` | GraphQL | No |
| `Mstore_*` (legacy FluxStore REST pack: social login, orders, Stripe intent, …) | REST | No. **Should not be**: legacy, and the Stripe route is broken (`stripe/stripe-php` not installed). Recommend disabling (see §5). |
| `MagentoEgypt_OdooConnector` | inbound REST | N/A (back office) |

### 3.3 Modules that need installation, configuration or API integration

**Configuration only (backend admin, no code):**

1. Turn on order cancellation: `sales/cancellation/order_cancellation_enabled`, plus the allowed statuses.
2. Make the UAE postcode optional: `general/country/optional_zip_countries` must include `AE`.
3. Create the CMS block `hm_app_faq`. Check `hm_delivery_promise`, `hm_home_promos`, `hm_home_trust`, `hm_footer_customer`, `hm_footer_legal`, `hm_home_sell` in **both** `en` and `ar`.
4. HubApp admin settings (Stores › Configuration › HubApp):
   - contact: replace the placeholder US WhatsApp number
   - `version` min/latest per platform
   - `search.trending_terms`
   - the Algolia search key, so the app stops scraping storefront HTML
   - the `shipping` free-shipping threshold
5. Confirm the four feature flags (`returns`, `store_credit`, `whatsapp_login`, `push`) for the `en` and `ar` scopes. They are on by default in `config.xml`, but an override at store scope switches the feature off in the app.
6. Replace the test catalogue and remove the Egypt / EGP text. Confirm base and display currency are **AED** (the committed SQL dump is still EGP).
7. Payment and shipping configuration for the UAE: decide on the gateway (§4 P0-2), then enable it and set `vtablerate` / flat rate for the emirates.

**Installation / external setup:**

1. **Firebase:**
   - create the Firebase project
   - add `google-services.json` and `GoogleService-Info.plist` to the app (flavour-specific)
   - upload the APNs key
   - add the FCM v1 service-account JSON to `MagentoEgypt_PushNotification`
2. **Deep links:**
   - publish `https://hub-market.magento2.click/.well-known/assetlinks.json` (Android package `com.hubmarket.app` + release SHA-256)
   - publish `/.well-known/apple-app-site-association` (served as `application/json`, no redirect); nginx needs an explicit location block because Magento's router will 404 these paths
   - enable the iOS Associated Domains entitlement (currently commented out)
3. **Payment gateway SDKs** in the app, if the native route is chosen: `tabby_flutter_inapp_sdk`, Tamara's in-app SDK, and the Paymob Flutter SDK or a WebView flow.

**API integration (new code):** see §4.

### 3.4 Existing integrations that are incomplete or not working correctly

| Integration | Problem | Fix | Owner |
|---|---|---|---|
| HubApp contract vs committed schema | `lib/core/graphql/schema.graphql` was introspected **before** HubApp was deployed. `validate_ops.py --live-only` reports 65 problems, all `hm*` fields. CI validates against the contract overlay, so a missing deployment would not be caught. | Re-run `tool/introspect_to_sdl.py` against production, commit, and make CI fail on `--live-only` problems. | App + DevOps |
| Push notifications | App: no Firebase config, so registration never runs. Backend: only an **admin-scheduled** cron (`PushNotification/Cron/SendScheduled.php`); there are **no observers** for order placed / shipped / delivered / return-updated. | Firebase setup (above). Backend: add event observers that send FCM to `magentoegypt_push_notification_device` tokens for the customer, with the deep link in the payload. | Both |
| Notification inbox | The inbox is device-local (Hive, 30 days). It is lost on reinstall and empty on a second device. | Optional `hmNotifications` query plus `hmMarkNotificationRead`, backed by the existing device/push tables. | Both (P2) |
| Deep links | App Links unverified (`assetlinks` 404); iOS Universal Links not declared. | See §3.3. | Both |
| Order cancellation | Built in the app but hidden (backend setting off). | Config. | Backend |
| Algolia key | The app falls back to scraping `window.algoliaConfig` from storefront HTML. This breaks silently if the theme changes. | Always publish `hmAppConfig.algolia`, and remove the scrape once that is confirmed. | Backend, then App |
| Reorder | Done client-side by re-adding each line, so configurable / bundle / out-of-stock errors are handled ad hoc. | Use core `reorderItems(orderNumber)`, which returns `userInputErrors`. | App |
| Saved cards | The list/delete screen exists, but nothing can create a card (no gateway). | Comes with P0-2. | Both |
| Guest checkout OTP | Mutations exist, app support is built, but the flag is off. | Product decision: turn on if guest COD fraud is a concern. | Product |
| Paymob module | Hard-coded to `https://accept.paymobsolutions.com` (Paymob **Egypt**); its methods (valU, Sympl, Souhoola, Aman kiosk, …) are Egyptian. It cannot take AED from a UAE merchant account. | Replace or configure it for Paymob UAE (`uae.paymob.com`), or choose another UAE gateway. | Backend |
| Legacy `Mstore_*` REST | Unused by the app; `/V1/mstore/stripe/payment-intent` is broken; `/V1/mstore/social_login` is an unauthenticated sign-in surface. | Disable the module set. | Backend |

### 3.5 Features that need more backend development (Magento), app changes, or both

| Feature | Magento | App |
|---|---|---|
| Online payments (card / Tabby / Tamara) | ✔ GraphQL payment session + return/webhook handling | ✔ checkout step, WebView or SDK, result screen |
| Order-status push notifications | ✔ observers + FCM sender | ✔ Firebase config, tap routing (mostly built) |
| Seller coupons | ✔ GraphQL wrapper | ✔ per-store coupon field in the cart groups |
| Review photos / likes / verified / replies | ✔ GraphQL extension of `ProductReview` | ✔ UI (currently hidden) |
| Delivery date / gift wrap | ✔ GraphQL wrapper over Amasty | ✔ checkout UI |
| Invoices / PDF | ✔ PDF endpoint (optional) | ✔ invoice view / download |
| Guest returns | ✔ guest variants in HubAppReturns | ✔ "Return an item" guest tab (hidden today) |
| Location picker | ✔ geocoding / emirate-area mapping (none on the website either) | ✔ map / "use my location" |
| Product custom options | — | ✔ render options and send `entered_options` / `selected_options` |
| Compare, gift message, change email, guest newsletter | — (core GraphQL exists) | ✔ |
| Crash reporting / analytics | — | ✔ Crashlytics + Analytics (consent-gated) |

---

## 4. Detailed work items: endpoints, integration work, owner

Priorities:
- **P0**: blocks launch or revenue
- **P1**: website feature parity that customers will notice
- **P2**: nice to have
- **P3**: only if the business wants it

### P0-1 · Prove the live backend matches the contract
- **Owner:** App + Backend/DevOps · **Effort:** S
- **Work:**
  1. Deploy `figma-parity-home` (HubApp family, AlgoliaVendor, Tabby, Tamara) to production if not already done.
  2. Re-introspect the schema and commit `schema.graphql`.
  3. Make `validate_ops.py --live-only` a CI gate.
  4. Smoke-test each `hm*` query for both store views.
- **Endpoints:** all `hm*` (see `app/code/MagentoEgypt/HubApp/docs/CONTRACT.graphql`).

### P0-2 · Online payments (card + BNPL)
- **Owner:** Both · **Effort:** L
- **Decision first:**
  - Which UAE card gateway: Paymob UAE, N-Genius (Network International), Checkout.com, Stripe, …
  - Confirm that Tabby and Tamara merchant accounts are live for AED.
- **Magento (new module, e.g. `MagentoEgypt_HubAppPayments`):**
  - `query hmPaymentMethodsConfig(cart_id)`: per-method display data (Tabby/Tamara eligibility, instalments, logos).
  - `mutation hmStartPayment(cart_id, method_code, return_url)`: places the order in `pending_payment` (or reserves the quote) and returns `{ order_number, redirect_url | sdk_payload, expires_at }`.
    - Paymob: create the payment key and return the iframe / unified-checkout URL.
    - Tabby: call `/api/v2/checkout` and return `web_url` / session.
    - Tamara: create the checkout session and return `checkout_url`.
  - `query hmPaymentStatus(order_number)`: `PENDING | PAID | FAILED | CANCELLED`, so the app can poll after the redirect.
  - Webhooks (Paymob HMAC callback, Tabby and Tamara webhooks; Tabby's already exists) must invoice the order or cancel it and release stock and credit.
  - Fix or replace `Accept_Payments` for AED (§3.4).
- **App:**
  - Allow the deferred methods in `payableInApp` (`features/checkout/domain/checkout.dart`).
  - Open `redirect_url` in an in-app browser or WebView and intercept `return_url`.
  - Or use the Tabby / Tamara Flutter SDKs.
  - Poll `hmPaymentStatus` and show the success screen or the existing `payment_failed_sheet.dart`.
  - Add Tabby/Tamara PDP and cart promo widgets.
- **Apple Pay / Google Pay (optional, P1):** usually supplied through the chosen gateway's SDK; needs the merchant ID and domain verification.

### P0-3 · Push notifications end to end
- **Owner:** Both · **Effort:** M
- **Magento:**
  - Add observers on `sales_order_place_after`, `sales_order_save_after` (status change), shipment creation, and the RMA status change (HubAppReturns events).
  - Queue a push for the customer's devices.
  - Payload: `title`, `body`, `data.deeplink = hubmarket://app/orders/<number>` (or the HTTPS equivalent).
  - Respect the store view language.
  - Clean up invalid tokens when FCM returns `UNREGISTERED`.
- **App:** add the Firebase config files per flavour, an APNs key, and confirm the `push` flag. Registration (`hmRegisterDeviceAndroid` / `hmRegisterDeviceIos`) and the tap routing already exist.
- **Endpoints (existing):** `hmRegisterDevice`, `hmUnregisterDevice`.

### P0-4 · Deep links
- **Owner:** Both · **Effort:** S
- **Magento / server:** serve `/.well-known/assetlinks.json` and `/.well-known/apple-app-site-association` from nginx (static, `application/json`).
- **App:** enable Associated Domains (`applinks:hub-market.magento2.click`) and add `autoVerify` intent filters (already declared). Then test product, category, store, order and CMS URLs.

### P0-5 · Backend configuration and content clean-up
- **Owner:** Backend (admin) · **Effort:** S
- Order cancellation on.
- `AE` postcode optional.
- `hm_app_faq` and the other `hm_*` CMS blocks in EN and AR.
- Real WhatsApp number in HubApp contact.
- AED everywhere; remove the Egypt text and test catalogue.
- Version gates set.
- Algolia key published in `hmAppConfig`.

### P0-6 · Security hygiene in the public website repo
- **Owner:** Backend · **Effort:** S
- **Problem:**
  - `magentoegypt/multivendor` is **public**.
  - It tracks `auth.json`, which holds live Composer credentials for repo.magento.com, Amasty, Mageworx and Scommerce.
  - It also tracks `multi_vendor_live.sql`, a full database dump including the customer table.
- **Fix:**
  1. Rotate those keys.
  2. Remove both files from the repo and its history, or make the repo private.
  3. Review the dump for customer PII exposure.

This is not an app gap, but it affects the same systems and should go first.

### P1-1 · Seller (vendor) coupons
- **Owner:** Both · **Effort:** M
- **Magento:**
  - `mutation hmApplyVendorCoupon(cart_id, vendor_id, code)`
  - `hmRemoveVendorCoupon(cart_id, vendor_id)`
  - `Cart.hm_vendor_coupons { vendor_id, code, discount }`
  - These wrap the logic behind `/V1/carts/mine/vendor-coupons`.
- **App:** a coupon field in each store group of the cart, and the per-store discount line in the totals.
- **Check first:** whether core `applyCouponToCart` already accepts seller coupon codes. If it does, only the display work remains.

### P1-2 · Review extras (Lof_ProductReviews)
- **Owner:** Both · **Effort:** M
- **Magento:**
  - Extend `ProductReview` with `hm_images[]`, `hm_verified_purchase`, `hm_likes`, `hm_liked_by_me`, `hm_reply { text, author, created_at }`.
  - Add `ProductInterface.hm_rating_histogram`.
  - Mutations: `hmLikeReview`, `hmReportReview`, and `hmCreateReview` (core input plus base64 / pre-uploaded images).
- **App:** show the hidden photo strip, verified badge, helpful votes, seller reply and histogram; add photo upload to the review form (the returns photo upload can be reused).

### P1-3 · Invoices and order documents
- **Owner:** App, with optional Backend · **Effort:** S–M
- **App only (quick win):** render invoices, shipments and credit memos from the existing `customer.orders { invoices, shipments, credit_memos }` data in a "Documents" card.
- **Magento (optional):** `query hmOrderDocument(order_number, type: INVOICE|SHIPMENT|CREDITMEMO, id)` returning a short-lived signed PDF URL (reusing the core PDF renderers), for download and share.

### P1-4 · Delivery date, gift wrap, gift message
- **Owner:** Both · **Effort:** M
- **Magento:**
  - `query hmDeliveryDateConfig(cart_id)`: available dates and time slots.
  - `mutation hmSetDeliveryDate(cart_id, date, time_slot, comment)`.
  - `mutation hmSetGiftWrap(cart_id, wrap_id | null)`.
  - These wrap Amasty's REST models.
- **Gift message:** core `setGiftOptionsOnCart` already exists, so this part is app-only.
- **App:** extra fields on the shipping step, shown on the review step and in the order detail.

### P1-5 · Guest returns
- **Owner:** Both · **Effort:** S–M
- **Magento:** `hmGuestReturnableOrder(number, email | phone, lastname)` and `hmGuestCreateReturn(...)`, returning a guest return token for follow-up messages. Alternatively, let `hmReturnableOrder` accept the `guestOrderByToken` token.
- **App:** enable the hidden guest "Return an item" tab.

### P1-6 · Product custom options
- **Owner:** App · **Effort:** M
- Render `CustomizableOptionInterface` (field, area, drop-down, radio, checkbox, date) on the PDP.
- Send `entered_options` / `selected_options` with `addProductsToCart`, and show them in the cart and order lines.
- **Check first:** how many catalogue products have required options. Today those products **cannot be added to the cart from the app**.

### P1-7 · Crash reporting and analytics
- **Owner:** App · **Effort:** S
- Firebase Crashlytics and Analytics (consent-gated, matching the existing Algolia Insights consent), with purchase and checkout events aligned with the website's GA4.

### P2 items

| Item | Owner | Endpoints / work |
|---|---|---|
| Server-side notification inbox | Both | `hmNotifications(page)`, `hmMarkNotificationsRead(ids)` |
| Product compare | App | core `createCompareList`, `addProductsToCompareList`, `compareList` |
| Change email | App | core `updateCustomerEmail(email, password)` |
| Guest newsletter | App | core `subscribeEmailToNewsletter` |
| Reorder via core | App | core `reorderItems` |
| SMS / WhatsApp notification preferences | Both | `hmSmsPreferences` / `hmSetSmsPreferences` over Vnecoms SMS settings |
| Location picker | Both | Google Places / reverse geocoding in the app; optional backend `hmResolveArea(lat, lng)`. The website theme's `MagentoEgypt_DeliveryAvailability` folder has no module behind it, so fix the website too. |
| Remove legacy Mstore REST | Backend | disable `Mstore_*` modules |
| Become a seller (native) | App | core-adjacent `createVendor`; the WebView is acceptable today |

### P3: only if the business wants them (not on the website today)

These need **both** new backend modules and app work. Do not treat them as parity gaps:

- vendor chat / messaging
- follow a store
- social login (Apple + Google; Apple is mandatory on iOS once any social login ships)
- reward points
- gift cards
- size guide
- "frequently bought together"
- delivery ETA per store
- native blog
- store locator
- request a quote

---

## 5. Recommended sequence

| Sprint | Work |
|---|---|
| 1 | P0-6 security clean-up, P0-1 contract verification, P0-5 configuration and content, P0-4 deep links |
| 2–3 | P0-2 payments (gateway decision in week 1), P0-3 push end to end |
| 4 | P1-1 seller coupons, P1-3 invoices (app-only part), P1-6 custom options (if the catalogue needs it), P1-7 crash reporting |
| 5 | P1-2 review extras, P1-4 delivery date / gift options, P1-5 guest returns |
| Later | P2 list, then P3 only on business request |

**Rule for new backend work:** keep adding to the HubApp family. That means:
- `hm`-prefixed GraphQL
- the contract in `HubApp/docs/CONTRACT.graphql`, copied to the app's `lib/core/graphql/hubapp.graphql`
- a capability or feature flag in `hmAppConfig`, so older app builds hide what they cannot use

This is the pattern that made the existing parity possible. It also lets the app ship before the backend is ready, because a missing flag simply hides the feature.

---

## 6. Already at parity, or not a gap

- **Seller-side Vnecoms GraphQL** (`vendorOrders`, `vendorProducts`, …) belongs to the vendor app (`hubmarket-vendor-app`), not the customer app.
- **Absent on the website too:**
  - MGS blog, store locator, lookbook, guest wishlist, instant search, AJAX cart (decommissioned in Phase L)
  - Quotation (PDP button removed)
  - social login
  - reward points / gift cards
  - vendor chat / follow
- **Website-only presentation with app-native equivalents** (no API needed):
  - HubMarket_DynamicMenu (the app uses the category tree)
  - SellerTheme / VendorsPage styling
  - SearchLanding (the app has its own search screen with history and trending terms)
  - minicart cross-sell
- **Back office only:** OdooConnector, Bss Reindex, Commission / Withdrawal.
