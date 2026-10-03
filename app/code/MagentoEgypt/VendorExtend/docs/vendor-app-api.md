# Hub Market vendor app: backend API context (as of 2026-10-03)

You are working on the Hub Market **vendor (seller) mobile app**. The Magento 2.4.8 backend (Vnecoms
marketplace) was changed between 2026-09-24 and 2026-10-01. Everything below is live on production.
Use it as the contract. Don't work around the old behaviour.

**New since 2026-09-28** (details in the sections below and the change log at the end):
- Product text per store view (English / Arabic): new translations API (10-03, see "Product text per store view").
- Login codes arrive by **WhatsApp, not SMS**: the code screen must say so (TC76, 10-03, see WhatsApp OTP).
- WhatsApp codes: new limits and answers (PR #22, 09-30).
- Products: enable/disable applies at once, and numeric SKUs are editable (TC68).
- Lists: `total_count` is the real total.
- Shipments: the shipment list works.
- Dashboard: the charts are in store time (TC66).
- Deploy windows: expect short 503s during deploys; keep the retry.
- Edits waiting for approval: the admin can no longer approve a product without applying the seller's
  queued changes (TC68-QA03, 10-01). No app change is needed.
- Deals need an end date to get a countdown: send `special_to_date` with `special_price` (see Deals and
  countdown, 10-02).
- Homepage product rows now crop photos to fill a square (DEV01.38, 10-02). Upload square photos with the
  product centred (see Product images).
- Product page photos: 1200 px, shown whole in a square frame (QA01, 10-01). Upload guidance changed:
  1200x1200 px or more, and never screenshots (see Product images).

## Host and auth
- API host: `https://multi.magento2.click/rest/...`. Send a normal browser-like `User-Agent`, because
  requests without one get 403 at the web server.
- Store code in the path: `/rest/V1/...` is the default store view (English). `/rest/ar/V1/...` and
  `/rest/en/V1/...` pick the language for localised text (category names, messages).
- Seller calls use the **seller's customer token**, from WhatsApp OTP login (below) or the customer
  token endpoint. **Do not ship or use the admin integration token** ("flutter-app-integration",
  prefix `qvy8`). It is being revoked once the new app build is published. Every seller-scoped
  route works with the seller's own token.

## Currency
- The store currency is now **AED** (it was EGP until 2026-09-26). Every formatted amount, such as the
  dashboard's `credit_amount`, `lifetime_sales` and `average_orders`, comes back as "AED 1,234.00"
  or "1,234.00 د.إ.". Display it as returned. Don't hard-code a currency symbol or code.

## Account state: 403 means "show the message"
Every seller-scoped call returns **HTTP 403** with a readable `message` when the account can't act:
- "Your seller account is pending approval. You can sign in once it has been approved."
- "Your seller account is disabled. Please contact support."
- "Your seller account has expired. Please contact support."
- "This account is not registered as a seller."
Show the message and stop. It is not a network or generic error, and retrying won't help.

## WhatsApp OTP
- **Codes are sent by WhatsApp only. There is no SMS gateway.** They arrive in the WhatsApp chat with the Hub
  Market business account, using the `otp_new` template.
- **TC76 (10-03) was this, not a delivery failure:**
  - QA asked for a login code 3 times at 07:51–07:52 UTC. The server sent each one to the account's number, and
    Meta's WhatsApp API answered `accepted`.
  - No code was ever entered. On 10-01 the same number received codes and entered them 21 s and about 1.5 min
    later.
  - The app's dialog says only "Enter the code sent to +20…", and the keyboard offers "Autofill code from
    messages", so the tester watched for an SMS.
- **What the app should change:**
  - Say WhatsApp in the code dialog, e.g. "We sent a 6-digit code to your WhatsApp at +20…" /
    "أرسلنا رمزًا من 6 أرقام إلى واتساب على +20…".
  - Show the `message` from `send` ("OTP sent successfully. Please check your WhatsApp.").
  - Don't rely on SMS autofill (Android SMS Retriever / iOS one-time-code from Messages); it never fires for a
    WhatsApp message. Optionally offer an "Open WhatsApp" button.
- If the client ever wants SMS codes too, that needs an SMS provider account (sender ID and credit); the backend
  has none today.
- `POST /V1/whatsapp/otp/send` body `{"mobile": "...", "type": "..."}`
- `POST /V1/whatsapp/otp/verify` body `{"mobile": "...", "otp": "...", "type": "...", "password": "..."}`
- `type` values: `VENDOR_LOGIN`, `VENDOR_REGISTER`, `VENDOR_FORGOTPASS`, `VENDOR_UPDATEMOB`
  (customer flows: `LOGIN`, `REGISTER`, `FORGOTPASS`).
- The response is always HTTP 200 with `{status: "success"|"error", message, token}`. **Check `status`.**
  - `VENDOR_LOGIN` success: `token` is the seller's bearer token.
  - `VENDOR_REGISTER` success: `token` is a **registration ticket** for `POST /V1/vendors/register`.
    It is single use and valid 30 minutes.
  - `VENDOR_FORGOTPASS`: send the new `password` in the same verify call; `token` is empty.
- The request and response shapes are unchanged (`status` / `message` / `token`). The rules behind them
  changed on 2026-09-30 (PR #22). Always show `message` as returned.
- **Sign-in and reset (`VENDOR_LOGIN`, `VENDOR_FORGOTPASS`, and customer `LOGIN` / `FORGOTPASS`):**
  - `send` answers `success` "OTP sent successfully. Please check your WhatsApp." for **every** number,
    even one with no account, so the answer never reveals which numbers are registered. Don't expect
    "Mobile number not found." any more.
  - The code goes to the number **stored on the account**, never to the number as typed.
  - `verify` gives one answer for a wrong or expired code, an unknown number, or a number shared by
    several accounts: "That code is incorrect or has expired. Check it, or ask for a new code."
- **New number (`VENDOR_REGISTER`, `VENDOR_UPDATEMOB`, `REGISTER`, `UPDATEMOB`):** the code goes to the
  number as sent. "Mobile number already exists." comes back if another account has that number in any
  spelling. The seller's own number never counts on `VENDOR_UPDATEMOB` (TC73).
- **Limits (per number):**
  - 5 code requests per hour. The 6th answers "Too many code requests. Please try again in N minutes."
    Every request counts, even a refused one, so **test only with QA's own number**: each send is a paid
    WhatsApp message, and hammering a real seller's number uses up that seller's hour.
  - A code expires after 15 minutes.
  - After 5 wrong codes, that number's code checks are locked for 15 minutes: "Too many incorrect
    codes. Please try again in N minutes."
  - The resend cooldown (about 30 s, "Please wait N seconds before requesting another code.") still
    applies on the new-number flows.
  - Wrong codes **no longer** count toward the account's password lockout, so password sign-in can't be
    locked through codes.
- **Number spellings:**
  - UAE numbers in any spelling (`05X…`, `5X…`, `9715X…`, `009715X…`, `+9715X…`) are one number,
    stored as `+9715X…`. New sellers are saved in that form.
  - The Egyptian rules are unchanged: "+20 100…", "0100…" and "20100…" are the same number.
  - Old rows stored in other spellings are still found; nothing rewrote them.

## Staying logged in: token refresh (TC70)
- Seller tokens are JWTs that expire **24 hours after they were issued** (60 minutes until the evening
  of 2026-09-28). Tokens issued before the change keep their original 60-minute expiry.
- `POST /V1/vendors/me/token/refresh` with `Authorization: Bearer <current token>` and no body returns
  **200 and a JSON string**, the new token, in the same format as the OTP login token, with a fresh
  24 hours. Store it and use it from then on.
- Refresh **revokes nothing**: the old token keeps working until its own expiry, and so do the
  seller's tokens on other phones. Two devices on the same seller no longer sign each other out.
- An expired, revoked or garbage token gets **401**: send the seller to OTP login. A pending,
  disabled or expired seller account gets the usual **403** message.
- Call it when the app opens or comes back to the foreground and the stored token is more than
  about 12 hours old. A seller who doesn't open the app for 24 hours signs in again.
- Sign out: `POST /V1/integration/customer/revoke-customer-token` with the seller token. It signs
  the seller out on **every** device (Magento revokes per seller, not per token), then clear the
  stored token.
- On a 401 in the middle of a form, keep what the seller typed and return them to it after they
  sign in again.

## Seller self-service (new endpoints)
- `PUT /V1/vendors/me`: update the seller's own profile. Protected fields (vendor id, status, group,
  etc.) are ignored if sent. Validation errors come back as 400 with the reason.
- `DELETE /V1/vendors/me`: delete own seller account.
- `GET /V1/vendors/me/stockItems/:sku`: stock for one of the seller's own products. 404 "The product
  "X" was not found among your products." for anyone else's.
- `GET /V1/vendors/me/categories?rootCategoryId=&depth=`: category tree for the product form.
- `POST /V1/vendors/register` body `{"vendor": {...}, "password": "...", "registrationToken": "<ticket>"}`
  - The ticket comes from verify with `VENDOR_REGISTER`.
  - Errors (400): "The registration token is invalid or has expired. Please verify your mobile
    number again.", "Mobile number already exists.", "Password is required."
  - New sellers start **pending approval**, so login returns the 403 message above until an admin
    approves them.

## Products: `/V1/vendors/product/save`
- **POST (create)**
  - The product always starts **Pending New** and belongs to the calling seller. Any `approval`
    or `vendor_id` you send is ignored.
  - **SKU rule:** a new SKU must match `^[A-Za-z0-9_-]+$` (English letters, digits, `-`, `_`).
    Anything else returns 400 with the message: SKU "X" is not allowed. Use only English letters
    (A-Z, a-z), digits (0-9), "-" and "_".
  - A SKU already used by another seller's product is refused: "The SKU "X" is already used by
    another product. Please choose a different SKU."
- **PUT (edit)** body `{"product": {...}, "attributes": ["name", "price", ...]}`
  - `attributes` lists the fields you are changing. `approval` and `vendor_id` are always
    ignored, so **stop sending the approval dropdown**; it does nothing.
  - Edits are saved at the default scope: **one value per field for both Arabic and English**,
    the same as the web seller panel. The old behaviour of changing only the English store is gone.
    To edit a store view's own text (English or Arabic), use the translations API (see "Product text per
    store view"), not this PUT.
  - Changing the SKU through `attributes: ["sku", ...]` follows the same SKU rule.
  - Existing products whose SKUs are Arabic can still be edited. The rule only applies to new SKUs.
- **Approval flow**, which is expected behaviour, not a bug:
  - Editing a product that is still Pending, or was rejected, applies the change and puts it
    (back) to **Pending**.
  - Editing an **approved** product queues the change for admin review and sets the product to
    **Pending Update**. On this storefront a Pending Update product is **offline until the admin
    approves the edit**, so the app should say so after saving an edit to a live product.
  - When the admin approves, the product goes live immediately.
  - Until then, `GET` returns the **current live values**, not the queued ones. So an edit form
    reopened after saving shows the old name and price. Nothing has been lost: the change is waiting.
    The app's "Waiting for admin approval: <fields>" message after saving is the right feedback.
    Keep it.
  - **QA03 "edits revert" (10-01):** the server did receive the app's name and price edits. They
    were queued as expected. The admin then approved the product from Catalog > Products by setting
    Approval = Approved and saving, which kept the old values and left the queue unapplied. Since
    `920da6428`, that page warns about the queued change, and saving as Approved applies it, the same
    as Marketplace > Manage Pending Products > Approve. The app API is unchanged.
- **Enable / disable (TC68):** send `"status": 2` (disable) or `1` (enable) with `"attributes": ["status"]`.
  It applies immediately, with no admin review, even on an approved product, and the product leaves or
  returns to the storefront at once. Listing unchanged `media_gallery_entries` / `category_ids`
  alongside it is fine, because unchanged fields are ignored. **Careful:** `category_ids: []` on a
  product that has categories is a real change (it removes them) and goes to review.
- Numeric SKUs such as "2345" work: an exact SKU match always wins (it used to be read as a product id
  and refused, TC68). A refusal reads `You are not permitted to save product "<name>".`
- Product list: `GET /V1/vendors/product?searchCriteria[...]` (seller-scoped).
- `DELETE /V1/vendors/product/:sku` (seller token): deletes one of the caller's **own** products and
  returns `true`. Another seller's SKU and a missing SKU both return 404 "The product "X" was not
  found among your products." Pending or disabled sellers get the 403 account messages. It was
  fixed on 2026-09-28; it used to answer 500 for every seller.
- Product names: send what the seller typed, at any length up to 255 characters. The server no
  longer rewrites names afterwards. An Odoo sync used to revert them; that was fixed on 09-28.
- **Images (TC71):** give the first (main) image **all four roles**: `image`, `small_image`,
  `thumbnail` and `swatch_image`.
  - `small_image` is what listings use: the seller's store page, categories and search. A product
    whose `small_image` is missing or `no_selection` shows a placeholder there, even though its
    product page shows the photo.
  - Nine products created by earlier app builds were backfilled on 09-28, so no app-side migration
    is needed. A product created with no image at all (for example "Test 90", "Test 91") still
    shows a placeholder until the seller uploads one.

## Product text per store view (2026-10-03)
Built from the app team's spec (backend handoff of 10-03), with **one change: `stores` lists `en` as well
as `ar`.** Seller token on every route; another seller's SKU returns 404 "The product "X" was not found among your
products.", as DELETE does.
- **Why `en` is listed:** the catalogue stores names in two opposite ways.
  - About 300 products keep **English at the default (store 0)** and Arabic at `ar`. This is the spec's case, e.g.
    FRS-STR-500.
  - About **214** (the real Egyptian catalogue) keep **Arabic at the default** and their English name at `en`
    (store 3).
  - So "default = English" is wrong for those 214. Read each language from its own store; don't assume what the
    default is.
- **What the form shows:**
  - English field = `en` value, else `default_values`.
  - Arabic field = `ar` value, else `default_values`.
  - Save each language to its own store with the PUT below. Use the product PUT (`/V1/vendors/product/save`)
    only for the shared default, e.g. when creating a product.
- `GET /V1/vendors/product/:sku/translations` returns `default_values`: `[{attribute_code, value}]` (store 0), and
  `stores`: `[{store_id, store_code, store_name, locale, values: [{attribute_code, value}]}]`.
  - The website's default store view comes first: `en` (3, en_US), then `ar` (1, ar_SA).
  - `value: null` = the store has no value of its own and shows the default. A value can equal the default: many
    older products have an `ar` row identical to it.
- `GET /V1/vendors/product/translations` (no SKU) has the same shape for a product not created yet, every value
  null. Ask for it when the seller starts a new product.
- **Fields:** `name`, `short_description`, `description`, `meta_title`, `meta_keyword`, `meta_description`.
  `url_key` is not translatable (it would change product links).
- `PUT /V1/vendors/product/:sku/translations` body:
  `{"translations":[{"store_code":"ar","values":[{"attribute_code":"name","value":"…"}]}]}`
  - Several stores and fields can go in one call.
  - `null` or `""` **removes** the store's own value, so that store shows the default again.
  - Unchanged values are ignored; a PUT that changes nothing changes nothing.
  - `name` is at most 255 characters.
  - The response is the GET shape with the live values.
  - Errors (400):
    - `Unknown store view "fr". Use one of: en, ar.`
    - `"url_key" cannot be translated. Translatable fields: …`
- **Approval, the same rule as product edits:**
  - **Pending New, Not Submitted or Unapproved** products are saved at once in that store.
  - **Approved (or already Pending Update)** products: the change waits for admin review. The product becomes
    **Pending Update (offline until approved)**, as after a name or price edit. The PUT response therefore still shows
    the *live* values, not the queued ones. Tell the seller the change is waiting for approval.
  - Several translation PUTs for the same store merge into one pending change.
  - On approval it is applied to that store view only; the admin product page names the store view.
- **New products:** POST the product (default text), then PUT the other language. A new product is Pending New, so
  the PUT is saved at once.
- **No cleanup of `en` values:** the spec suggested deleting a product's `en` value when the seller saves the default.
  That would erase the English names of the 214 Arabic-default products, so it is not done. Edit English through
  this API instead.

## Product images (2026-10-01)
- Upload as before: the first gallery image gets all four roles (`image`, `small_image`, `thumbnail`,
  `swatch_image`; see Products). The API returns paths such as `/2/f/name.jpg`. Show them from
  `https://hub-market.magento2.click/media/catalog/product` + path.
- The storefront also serves a WebP copy of every resized ("cache") image, at the same URL plus `.webp`
  (`…/media/catalog/product/cache/<hash>/2/f/name.jpg.webp`). Since 10-01, new copies are encoded from the
  original upload at high quality (q88), not from Magento's quality-80 JPEG, so newly uploaded photos look
  sharper on the website. Existing images were not re-encoded.
- If the app ever asks for those `.webp` URLs, use the **hub-market.magento2.click** host. A missing WebP
  there returns the original JPEG/PNG; on multi.magento2.click it returns 404. Original upload paths (not
  `/cache/`) have no `.webp` copy.
- WebP copies appear within about 10 minutes of an upload (a background job). Until then, the hub-market
  host returns the JPEG.
- **What sellers should upload:** the original photo file, **1200x1200 px or more**, square, product centred,
  plain background.
  - Since 10-01, category, search and homepage cards show a 600x600 square image. The product page shows a
    1200 px image in a square frame (up to ~900 px wide on desktop, so 2x and 3x screens use all 1200 px).
  - Category pages, search results and the product page fit the **whole** photo inside the square on white and
    never crop it, so a wide or tall photo shows with white bands there.
  - **Homepage rows** (Today's Deals, Best Selling, Popular Products, the category rows) **crop** the photo to
    fill the square, since 10-02 at the client's request (DEV01.38). A wide or tall photo loses its edges there.
    This is why **square, product centred** matters: a square photo looks the same everywhere.
  - A smaller photo is never enlarged on the server. Under 600 px looks soft everywhere; under 1200 px looks
    soft on the product page.
  - **Do not upload screenshots** of a photo: they are only as big as the screen region captured (the ones
    uploaded on 10-01 were ~350–590 px). On 10-01, 297 of the 624 live products had a main photo under 600 px;
    only a re-upload fixes those.
  - Every size, including the product page's 1200 px, is made automatically when the photo is saved through
    the API or the admin. The app does not need to resize. Sending the full camera resolution is fine
    (Magento keeps the original for fullscreen zoom), but keep files reasonable (under ~5 MB) for upload time.

## Deals and countdown (2026-10-02)
- Today's Deals on the website and in the customer app list products with a live special price. The countdown
  shows only when a deal has a real end date, and it counts to the end of the soonest-ending deal's last day, in
  store time (Asia/Riyadh). A deal with no end date is listed but gets **no timer**. On 10-02 the countdown had
  vanished for exactly this reason: every timed offer had ended on 09-30, and the live ones had no end date.
- When a seller sets a deal price from the app, send the end date with it, in the same PUT/POST `product`:
  `"special_price": 450, "special_from_date": "2026-10-02", "special_to_date": "2026-10-31"`. List all three in
  `attributes` when editing. The deal is charged up to and including `special_to_date`, so ask for a last day, not
  a time.
- `special_price` and its dates are price fields. On an approved product, edits to them go to admin review like
  name and price (see Products).
- Data fix on 10-02: the 7 live deals (SKUs 1223, 22228, test30, 1231, 1260, code 41, TEF-CLIPSO-8L) now end
  2026-10-31, so the timer shows on web and app until then.

## Short description on the website (2026-10-01, DEV01.36)
- The product page now shows `short_description` **as entered**, under the price, **one line per item**
  (for example "Brand: Fresh", "Colour: silver", "Drawers: 5"). Send it as plain lines separated by line
  breaks (`\n`, `<br>` or one `<div>`/`<p>` per line). Formatting tags are removed, so only text and line
  breaks matter. Up to 20 lines are shown.
- One value serves both stores: app edits save at the default scope (see Products), so the English page shows
  the same text as the Arabic one. The owner chose that over an empty English slot. English-only text needs
  an English store-view value, which only the admin can set today.
- If `short_description` is empty, the page shows the first lines of `description` instead.

## Image URLs: use the original path
- Build image URLs from the API's original paths (`/media/catalog/product/<a>/<b>/<file>`), not from resized
  `/cache/<hash>/` URLs. On 10-01 at 11:16 UTC an admin "Flush Catalog Images Cache" deleted every resized image,
  and this server does not rebuild them on demand, so `/cache/` URLs answered 404 until they were regenerated
  (about 60 minutes). Original paths were never affected.

## Deploys and outages
- During a backend deploy, the API answers **503** (maintenance page) for a few minutes. Right at the
  end of a compile, a route can also answer a brief **404 HTML** page. The first call after a deploy
  can take 15-25 s while caches rebuild.
- Keep the app's retry-once and its Retry button for any non-JSON or 5xx answer. Treat a non-JSON
  answer as "server busy", not as the error text. On 09-30 10:26-10:29 UTC the app's OTP sends got
  503s during the PR #22 deploy; that was the deploy, not a bug.
- `GET /V1/vendors/product?searchCriteria[pageSize]=100` normally answers in 0.5-2 s (TC74, 09-29). The
  26 s and the failed load QA saw were a compile window.

## Orders
- `GET /V1/vendor/order/:orderId`: only the seller's own orders; others return 404.
- `GET /V1/vendors/order` (and `/order/invoice`, `/order/memo`, `/credit/withdrawal`): `total_count` is
  now the seller's real total, not the page size (09-29). You can drop the "page until a short page"
  workaround.
- `GET /V1/vendors/order/shipment` works (it used to answer 500): `billing_address` / `shipping_address`
  are one-line text, e.g. "test test, test, krvwomv, ps,g,kj 12345, Egypt". The same applies to `/order/memo`.
- 56 orders from 09-19 to 09-27 (45 of them V8S2's) carry junk such as `{{var postcode}}` in the
  address. They are attack artefacts, not a formatting bug, and were **cancelled** on 09-29. Show address
  text as it comes back; never run it through a template engine.

## Dashboard
- `GET /V1/vendors/dashboard?period=7d` (unchanged path; `period` = 24h/7d/1m/1y/2y). Changed semantics:
  - `average_orders` = lifetime sales ÷ **paid** orders. Unpaid pending orders no longer drag it
    down; for seller V8S2 it is AED 722.22.
  - `total_products` leaves out configurable variants that aren't listed on their own, so it matches
    the storefront's seller page (V8S2: 13). Pending and disabled products still count.
  - Amounts are formatted strings in AED (see Currency).
  - Chart points (`order_chart_data`, `amount_chart_data`, `credit_chart_data`) are in **store time**
    (Asia/Riyadh), labelled "2026-9-28" per day, "2026-9-28 14:00" per hour (24h) and "2026-9" per month
    (1y/2y). An order at 02:30 Riyadh time now counts on that day, not the day before (TC66-QA02, 09-29).
- The storefront now matches this count too: the homepage "New Stores" card shows the same number
  as the seller page and `total_products` (V8S2: 13 everywhere). The homepage used to show 16.

## Seller display name
- The storefront shows a seller as its store name, then its company name, then its **seller code**
  (for example `V8S2`).
- A company name of just "0" counts as empty. Two test sellers (V3S2, V8S2) had it, and the
  homepage titled a store "0". If the app shows seller names, apply the same fallback: store name,
  then company (if not empty and not "0"), then seller code.

## Not changed / out of scope
- The storefront GraphQL isn't used by the vendor app. It was fixed separately (filters now work).
- Untested end to end because it would create real accounts or send real WhatsApp messages: the
  full register success path, `DELETE /vendors/me`, and the OTP resend cooldown. Test these against
  staging or test numbers when the app flow is ready.

## Change log (backend commits on `figma-parity-home`)
| Date | Commit | What changed for the app |
|---|---|---|
| 09-24 | `ca9cc1ad9` | Seller-scoped routes, 403 for pending/disabled, SKU takeover blocked, `/vendors/me*`, register, OTP limits |
| 09-26 | `a14f69302` | PUT ignores `approval` / `vendor_id`; approved-product edits go to Pending Update |
| 09-26 | (config) | Store currency EGP → AED |
| 09-28 | `46c21c357` | Average Orders over paid orders, Total Products without hidden variants, SKU rule, edits at default scope, Odoo no longer reverts names |
| 09-28 | `9edd4938b` | `DELETE /V1/vendors/product/:sku` works, own products only |
| 09-28 | `e71fa1922` | `POST /V1/vendors/me/token/refresh`; homepage store count = seller page (plus the 9-product image backfill, data only) |
| 09-28 | `a9b00d546` | Seller named "0" falls back to its seller code on storefront cards |
| 09-28 | (config) | Seller token lifetime 60 minutes → 24 hours |
| 09-28 | `1f6f12e22` | Token refresh no longer revokes the seller's other tokens |
| 09-29 | `092c98ec5` | TC68: numeric SKUs editable; status changes apply at once without review |
| 09-29 | `d60242a81` | Seller list endpoints report the real `total_count` |
| 09-29 | `a5e5c2e02` | Shipment (and credit memo) list no longer 500s |
| 09-29 | `ca119bba8` | TC66-QA02: dashboard charts in store time (Asia/Riyadh) |
| 09-30 | `bb1ca0555` | PR #22: WhatsApp code limits and one-answer verify, UAE number spellings; customer-app GraphQL (HubApp) |
| 10-01 | `812223da9` | Storefront WebP copies of new product images encoded from the original upload (q88); app API unchanged |
| 10-01 | `c32acb439` | Listing and homepage cards: 600x600 square image, whole photo fitted (upload square, 600 px or more) |
| 10-01 | `bf37d3676` | Product page shows `short_description` as entered, one line per item, on both stores |
| 10-01 | `3a3516f07` | Admin App Home Section editor loads (customer app Home content); app API unchanged |
| 10-01 | `920da6428` | TC68-QA03: approving from Catalog > Products applies the seller's queued edits (and warns about them); app API unchanged |
| 10-01 | `fc63a4189` | Product page: 1200 px photo, square frame, whole photo and thumbnails (never cropped); upload 1200 px+, no screenshots; app API unchanged |
| 10-02 | `eb37ab82d` | Homepage product rows crop photos to fill the square (DEV01.38); category pages keep the store's product order with no personalized re-shuffle (DEV01.39); app API unchanged |
| 10-02 | `b185f083c` | Website: the new delivery-area picker is hidden until that feature is finished (see Customer app); seller app unaffected |
| 10-02 | (data) | Today's Deals countdown back on web and app: the 7 live deals end 2026-10-31; deals need `special_to_date` to get a timer |
| 10-02 | (data) | Customer-app Home: Deals "All Deals" opens hmDeals, Featured Stores subtitle, Bundle Deals tile (4 tiles), "Sell on Hub Market" card (see Customer app) |
| 10-02 | `48c977b5d` | Customer app: admin-editable `badge` on every Home section (`HmHomeSection.badge`); Picked For You returns 16 (data) |
| 10-02 | `cf29043f8` | Customer app: `hmPickedForYou(user_token)`, Picked For You personalised per shopper (see Customer app) |
| 10-03 | (docs) | TC76: login codes are WhatsApp-only; the code screen must say WhatsApp, not rely on SMS autofill (see WhatsApp OTP) |
| 10-03 | `af8086986` | `GET/PUT /V1/vendors/product/:sku/translations`, `GET /V1/vendors/product/translations`: product text per store view, `en` and `ar` (see "Product text per store view") |

Still pending on the backend side: revoking the old admin token (`qvy8`) once the new app build is
published. The backend team does that on the product owner's go-ahead.

## Customer app (not this app)
PR #22 also added the customer app's GraphQL API (`hm…` fields: Home, deals, stores, bundles, returns,
store credit, push devices, order packages). Its contract is `app/code/MagentoEgypt/HubApp/docs/CONTRACT.graphql`.
For cached public reads, call it through `https://hub-market.magento2.click/graphql` (Varnish, varies on
the `Store` header). `multi.magento2.click` goes straight to origin with no cache. The seller app keeps
using REST on `multi.magento2.click`.
The customer app's Home is managed in the admin under Content > Elements > App Home Sections. The section editor
used to hang on its spinner and works since 10-01 (`3a3516f07`).

**Home content filled in from the UI audit (10-02, admin data, no API change).** These were the four gaps in the
customer app's audit doc, section 7, "Home on the live server". They can be dropped from that list.
- **Today's Deals:** the More link is cleared, so `more_link` is `null` and the app opens its Deals page (hmDeals).
- **Featured Stores:** `subtitle` is "Verified sellers on Hub Market" / "بائعون موثّقون على Hub Market".
- **HERO_BANNERS** now returns **4 tiles**. The new last tile is Bundle Deals:
  - kicker "🎁 This week only" / "🎁 هذا الأسبوع فقط";
  - subtitle "Buy more, pay less";
  - `tone` `#f26522` and `image_url` **null**, so draw a solid tile in the tone colour, as Figma does;
  - link type `BUNDLES`.
  The website still shows its 3 tiles.
- **"Sell on Hub Market":** a new last section of type `CMS_BLOCK` with `cms_block.identifier` `hm_home_sell`. The
  content is `<h3>` heading, `<p>` text, then a `<p>` holding only the "Start selling" / "ابدأ البيع" link to the
  store's seller sign-up page (`…/marketplace/seller/register/`).
- **Still app-side:** hero kickers in capitals. The admin text is mixed case and the website uppercases it in CSS;
  the app should do the same.

**Picked For You, personal (10-02, `48c977b5d`, `cf29043f8`):**
- **Refresh:** the Home section now returns **16** products, so the app can rotate them 4 at a time.
- **Badge:** every Home section has an optional `badge: String` (admin fields Badge (English) / (Arabic)); null
  means draw no pill. **Picked For You's badge is set since 10-02:** "AI ENGINE" / "محرك ذكي". Older builds don't
  query the field, so they show nothing. The new build must draw the pill **only when `hmPickedForYou` returned
  `personalized: true`**; otherwise the badge would make a false claim over top-rated picks.
- **Per-shopper picks:** `hmPickedForYou(user_token: String!, pageSize: Int = 4, currentPage: Int = 1)` returns
  `HmProductPage` with `items`, `total_count` (up to 16), `page_info` and the new `personalized: Boolean`.
  - It is **not cached**; send it as POST.
  - `personalized: true`: Algolia Personalization ranked the picks from this token's events.
  - `personalized: false`: no profile yet (or Algolia trouble), so it returns the same top-rated list as the Home section.
  - A malformed token returns a `graphql-input` error. Allowed characters: letters, digits and `_ = + / . -`, 1–129 characters.
- **Consent: ask first, like the website (owner's decision, 10-02).** Do not turn personalised picks on by default.
  Before the first behaviour event, ask the shopper with a short prompt such as "Personalise my picks?" (Allow /
  Not now). Keep the Settings switch so they can change their mind.
  - With no consent: send **no** Insights events and **don't** call `hmPickedForYou`; show Home's top-rated
    products without the badge.
  - Turning it off later: stop the events and drop the token.
  - The website does the same: no cookie consent means no Algolia token and nothing personal.
- **What the app must do,** or nothing is ever personal:
  1. Once the shopper has consented, send Algolia Insights events (product viewed, clicked, added to cart,
     purchased) under one stable `userToken` per install: an anonymous id, switched to the customer's token after sign-in, as the website does.
  2. Draw Home's cached Picked For You products first. Then call `hmPickedForYou` with that token and swap in its
     items when `personalized` is true. Refresh = next page.
  3. Show the badge and a "Personalised recommendations…" subtitle only when `personalized` is true; otherwise keep
     the admin's subtitle.
  - Algolia builds profiles in **batches, about an hour** after the first events, so a new shopper sees the top-rated
    list until then.
- **Test tokens with profiles:** `seed-en-000`…`seed-en-099` (store `en`) and `seed-ar-000`…`seed-ar-099` (store `ar`)
  return `personalized: true`. Any new token returns `false`.

**Delivery areas (work in progress, 10-02):**
- A new module, `MagentoEgypt_DeliveryAvailability`, was added on the server on 10-02 and is not in git yet. It adds:
  - a delivery-area picker: country, region, city, area;
  - `GET /deliveryavailability/check/index`;
  - a check at order placement.
- On the website the picker is **hidden** until the feature is finished (`b185f083c`). A header version is ready in the
  theme for when it ships.
- The order-placement check is a plugin on `QuoteManagement::placeOrder`, so it also covers orders placed from the
  customer app. Once an admin configures area rules (Stores > Configuration > General > Delivery Availability), an
  order whose shipping address is in a blocked ("red" or "blacklist") area fails with "Delivery is unavailable or requires
  a quotation for <product> at this address. Please change the address or remove the item." Show that message as it is.
- No rules are configured yet, so nothing is blocked today.
