# Hub Fulfillment / Fleet contract v1

Implementation under review. Not a deployed endpoint until the module and its schema are installed.

Base: `/rest/en/V1/hubfulfillment`. Fleet authenticates with a server-only Magento integration bearer with **MagentoEgypt_Fulfillment::fleet** ACL. Never expose it in the browser or vendor app. Customer tokens have no fleet access. Magento serializes these service results as JSON strings, so decode the HTTP JSON value, then decode again if it is a string.

- `GET /fleet/orders?afterId=0&limit=50`: `{contract_version:1, orders:[], next_after_id:123, has_more:true}`. Ascending entity IDs; limit 1–100. This is an inventory cursor, not an update cursor. Refresh known orders individually for changes.
- `GET /fleet/order/123`: one order.
- `POST /fleet/event`: `{ "payloadJson": "<JSON encoded event>" }`.

Order fields: `contract_version`, `order_id` (Magento entity ID), `order_number` (increment ID), `order_state`, `currency` (base currency), `destination`, `groups`.

Destination: `country`, `region_id`, `region`, `city_id`, `city`, `locality_id`, `street` (array), `postcode`, `recipient`, `telephone`, `verified_pin` (null until a verified coordinate integration exists). Address IDs on legacy orders may be zero. Never infer a pin from a city centroid.

Group fields: `group_id` (stable opaque string, paired with order_id), `vendor_ids`, `leg` (`direct|inbound|outbound`), `legacy`, `responsibility` (`marketplace|vendor|null`), `execution` (`own|third_party|null`), `carrier`, `tracking`, `note`, `state`, `version` (integer), `updated_at` (UTC ISO-8601 or null), `source_state`, `source` (source code or null), `label`, `parcel_count` (null; item count is not parcel count), `items`, `supported_actions`.

Each item: `sku`, `vendor_id`, `qty_milli` (1000 = one unit), `weight_grams` (per unit, nullable on legacy unknown weights). Legacy configured-unit weights are converted to grams. No price, rate or origin is manufactured for legacy orders. Fleet must require known weight, parcel count, verified destination pin and explicit operational release before own-fleet routing. An API inventory record is not a dispatch instruction.

Event JSON:

```json
{
  "order_id": 123,
  "group_id": "legacy-vendor-12",
  "action": "classify",
  "expected_version": 0,
  "operation_key": "unique-stable-operation-0001",
  "responsibility": "marketplace"
}
```

Returns `{applied:true|false, group:<saved snapshot>}`. GET the order to refresh items, source state and currently supported actions. Replay the identical event with the same operation key after a timeout. A changed payload/key reuse or stale expected_version is rejected. Each accepted operation increments only that group's version. No action sends notifications or books a carrier.

Actions:

| Action | Additional fields | Behavior |
|---|---|---|
| classify | responsibility | Legacy unclassified only. Vendor classification must respect configured vendor modes. Existing checkout allocation responsibility is immutable. |
| ready | none | Vendor marks its marketplace-managed origin group ready for pickup. Never available for shared outbound groups. |
| ship | execution; carrier and tracking for third_party | Vendor/legacy action records shipped_reported only. Confirmed marketplace allocation dispatch requires an existing Magento source shipment, and consolidated dispatch additionally requires hub receipts. Own delivery clears third-party tracking. |
| received | none | Inbound group receipt; confirmed allocated marketplace flow uses the warehouse workflow. |
| delivered | none | After dispatch/report. Vendor and legacy updates remain delivered_reported. |
| issue | note | Records an issue and previous operational state. |
| resume | none | Returns an issue to its previous state. |

States: `unclassified`, `planned`, `ready`, `dispatched`, `shipped_reported`, `received`, `received_reported`, `delivered`, `delivered_reported`, `issue`. Canceled/closed orders expose no actions. Fleet has **no mutation actions on vendor-managed groups**. Use supported_actions rather than inventing transitions. Source state (`planned|dispatched|received|delivered|legacy_unallocated`) is separate from reporting state. Shipping reports do not reserve/deduct MSI stock or modify the original order charge.

Vendor surface: existing vendor order page. Owner-authenticated customer REST equivalents: `GET /vendor/order/:orderId`, `POST /vendor/dispatch` with the same payloadJson wrapper. The vendor ID comes from the authenticated active owner account; it is never accepted from the payload. Marketplace groups only expose ready-for-pickup to the owning vendor. Vendor customers never receive fleet credentials.

QA must use the isolated synthetic database for mutations. Actual order IDs can be checked read-only, with customer address/phone omitted from published evidence. No real customer dispatch, shipment creation, carrier booking or payment is authorized as a test.
