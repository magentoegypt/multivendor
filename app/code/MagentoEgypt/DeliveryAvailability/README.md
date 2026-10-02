# Delivery Location & Availability — first increment

Target: Hub Market (`/var/www/multi.magento2.click`). Depends on the existing City Manager location directory. No new schema, inventory writes, order creation or shipping price changes.

## Implemented

- Public area-check endpoint: `GET /deliveryavailability/check/index?country=EG&region=...&city=...&locality=0&sku=...`.
- Canonical, active City Manager IDs; invalid/mismatched destination returns HTTP 400. Service/configuration errors return 503 and are not converted into coverage.
- Admin configuration: **Stores → Configuration → General → Delivery Availability → Destination restrictions**. Initial developer-facing JSON editor with ACL protection, schema and location validation.
- Rules target country, optional city, optional locality and exact SKU (or `*`). Strongest matching restriction wins: blacklist, red, green. A green SKU rule cannot override a global blacklist.
- No matching rule returns `unconfigured`; it does not promise delivery, inventory, a warehouse, ETA or price.
- Web destination selector (session-scoped) uses the existing directory. Guests can browse without a location.
- Core Magento `QuoteManagement::placeOrder` validation checks actual shipping address and every physical cart item. Configured blacklist and red restrictions block placement. Virtual-only quotes bypass delivery rules. Existing unrestricted destinations retain existing checkout behavior.
- Flutter counterpart includes an EN/AR picker, area/product/cart coverage messages and default-address fallback. Explicit selection takes priority over account default; account-derived destination clears on logout. Shopping destination does not overwrite checkout address.

## Rule format

```json
[
  {"country":"EG","city_id":123,"locality_id":0,"sku":"*","status":"green"},
  {"country":"EG","city_id":456,"locality_id":0,"sku":"PRODUCT-SKU","status":"blacklist"}
]
```

IDs above are illustrative: replace them with active IDs from City Manager. Do not seed guessed business coverage. `red` is quotation required and blocks ordinary checkout until the quotation workflow is implemented. This is a deliberate initial policy, configurable by changing/removing the area rule.

## Boundaries / next increments

This is NOT the completed fulfillment system. It does not yet allocate MSI sources, filter search/category results, enforce vendor-specific coverage alternatives, calculate shipping fees, split/route fulfillment groups, manage settlement, provide a quotation request flow or persist order-level fulfillment snapshots. Warehouse stock is explicitly `not_evaluated` in the API. Standard Magento checkout continues to enforce its existing inventory and shipping methods.

Web saved-address fallback and inline per-line cart validation remain to be added; web currently provides a general coverage preview and server-side final restriction guard. Mobile destination is in memory for the current app session; it is not persisted across restarts. Changing quantity retains the same area rules; quantity-based source allocation is not implemented.

Backend custom payment integrations that bypass QuoteManagement must be audited before production rollout. Invalid legacy addresses in countries with restrictions must be corrected through City Manager before an order is placed.

## Release safety

Deployment adds only this module and enables it in app/etc/config.php. No blanket setup:upgrade: unrelated pending schema/patch changes must not be applied. Compile DI and deploy storefront assets under maintenance after other deployment processes have finished. Back up config and generated metadata. Roll back by disabling this module and restoring/recompiling DI metadata, then clean config/layout/block/full-page caches. Never change the separate legacy demo installation.
