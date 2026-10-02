# Hub Fulfillment — first implementation

This is a **read-only planning foundation**, disabled by default. It does not replace Magento/Vnecoms shipping, reserve inventory, create shipments, post vendor credits or transfer funds. It is not a completed fulfillment release.

## Implemented

- Source ownership: vendor warehouses and marketplace hubs use existing Magento MSI source codes and City Manager IDs.
- Vendor defaults and per-SKU overrides: `vendor`, `marketplace`, `hub`, or an empty list for no service. Product overrides intentionally replace the default, allowing a seller to ship selected products only. The product's real vendor ID must match the override.
- Direct delivery: a vendor may ship, or the marketplace may collect from that vendor's source. Source ownership remains enforced in either mode.
- Consolidation: every item must reach one common hub. Inbound legs are separate; the last-mile charge occurs once per hub group, including items already stocked there.
- Quantity-aware allocation proposals: deterministic source priority then source code; partial allocations never produce a purchasable quote. These are proposals, **not inventory reservations**.
- Coverage and rates: exact origin source, country/city/locality destination, direct/inbound/outbound leg, integer base fee, per-unit fee and per-started-kilogram fee. Locality specificity wins over city, then country. Missing rates never mean free shipping.
- Settlement projection: net goods and actual commission amounts are supplied by the caller; shipping revenue recipient and cost bearer are separate. The balanced projection shows vendor payable, marketplace contribution and carrier payable. It does not guess commission percentages or call accounting APIs.
- Magento preview adapter: validates current City Manager destination and delivery restrictions; derives vendor, weight, stock and website membership from Magento. MSI requested-quantity validation and aggregate salable quantity are checked; disabled/unlinked sources are excluded.
- Admin ACL-protected policy configuration and GET preview controller. Public output omits internal source identifiers, warehouse locations, stock quantities and cost estimates.

## API contract

Once enabled in an isolated review environment, GET `/hubfulfillment/quote/index` takes:

- `country`: EG, SA, AE or US; `region`, `city`, `locality`: City Manager IDs. Existing City Manager locality requirements apply, including locality optional where none is configured.
- `strategy`: `direct` or `hub`.
- `items`: JSON array of `{ "sku": "selected-simple-sku", "qty_milli": 1000 }`. Duplicate SKU lines are aggregated by the Magento adapter. At most 100 input lines; at most 1,000 units per SKU. One unit is 1,000 milli-units.

Responses distinguish `disabled`, `unsupported_product_type`, `unavailable` and `proposed`. A proposal has `reservation: not_reserved`, `checkout_binding: preview_only`, `price_basis: base_currency_excluding_tax` and shipping amounts in integer minor units. It is **not a delivery promise or a checkout payment amount**. Responses are private/no-store.

Only purchased physical simple SKUs are supported in this implementation. Configurable parent selection must be resolved to its child by the consumer; bundles, grouped items, virtual carts and mixed complex baskets need dedicated adapters. No frontend or app consumer has been wired yet.

## Policy

Stores → Configuration → General → Hub Fulfillment. Preview is off unless enabled explicitly. Policy is a versioned JSON object containing:

- `version: 1`, `currency` equal to the store base currency, `minor_digits: 2`.
- `sources`: `{code, vendor_id, kind: vendor|hub, priority, location: {country, city_id, locality_id}}`. Hubs have vendor ID zero. Lower priority numbers allocate first.
- `vendors`: `{vendor_id, modes: [...]}`. Unconfigured vendors receive no fulfillment offer.
- `products`: `{sku, vendor_id, modes: [...]}`.
- `rates`: `{id, source, leg, mode, destination, base_minor, per_unit_minor, per_kg_minor, cost_base_minor, cost_per_unit_minor, cost_per_kg_minor, revenue_owner, cost_owner}`. Owners are `vendor` or `marketplace`. Inbound rates additionally specify `hub`, with destination exactly equal to that hub's location. Shared outbound charges/costs belong to the marketplace; no arbitrary allocation to one seller.

See `Test/fixtures.php` for a complete, **fictional** policy. Its IDs, warehouse names and rates are test inputs and must not be imported as real business coverage. Save validation checks actual MSI source, vendor and active geographic references.

Source priority drives direct allocation; this is not a cheapest-route optimizer. Equal-specificity direct services use ascending rate ID as a deterministic tie-breaker. For consolidation, feasible common-hub proposals are compared by total customer shipping fee, then hub code. Source stock is a live snapshot; concurrent checkouts can change it immediately.

## Accounting boundary

`Settlement::project()` is a pure accrual model. `net_goods_minor` is goods revenue after discounts and excluding tax; `commission_minor` must come from the existing Vnecoms commission calculation. The model assumes the marketplace collects the sale and pays the carrier, deducting vendor-borne costs from vendor payable. It does not yet handle direct vendor collection or COD remittance.

The conservation rule is:

`customer_due = sum(vendor_payable) + marketplace_contribution + estimated_carrier_payable`

Marketplace contribution excludes payment fees, tax and operating expenses. Vendor payable is **not vendor profit**; vendor cost of goods is unknown. Negative shipping margins remain visible. Vnecoms currently calculates commission at invoicing and processes vendor credits/refunds through its own observers; this module intentionally does not register duplicate accounting observers.

## Verification

`php Test/run.php`: 36 standalone deterministic contract cases, including the Alexandria/Aswan/Asyut → Giza direct and consolidated scenarios, cross-vendor stock isolation, product overrides, stock shortages, rate specificity, fractional rounding and settlement conservation.

`php Test/runtime.php`: 7 read-only checks on the correct Hub Market Magento installation. It bootstraps `/var/www/multi.magento2.click`, resolves real dependencies, validates XML schemas and checks a real simple product against MSI using in-memory fictional policy values. No policy, inventory, order or settlement records are saved. Review this hardcoded environment path before using elsewhere.

## Remaining release work

1. Admin warehouse/rate grids and vendor ownership-enforced policy editor; JSON is only the initial administrator configuration interface.
2. Location-filtered category/search pagination and facets, including Algolia indexing/filter semantics and cache variation; an availability preview is not a filtered catalog.
3. Web, customer app and vendor app consumers, localized shipping breakdown and explicit direct/hub selection.
4. Authoritative checkout shipping carrier/totals integration with existing Vnecoms quote splitting; revalidation on the actual checkout address, selected SKUs, quantity and currency. Complex products, multishipping and tax need separate acceptance cases.
5. Durable versioned order/group snapshots, source allocation/reservation lifecycle, cancellation and race/concurrency handling. Recomputing later must not silently change an accepted order.
6. Shipment routing, hub receipt/consolidation events, labels and carrier/Fleet acceptance tests.
7. Idempotent persisted settlement ledger, invoice capture, partial/full refund reversal, COD reconciliation and integration with existing vendor credits and payouts.
8. Isolated environment end-to-end verification before enabling checkout charging or accounting. External sandbox accounts are still unavailable.

No schema changes or deployment commands are required to run the standalone tests. Do not use a blanket `setup:upgrade` on the current shared installation: unrelated schema drift already exists. This build has not been enabled or deployed into the live Magento module directory.
