# Hub Market Fulfillment

Operational implementation for Magento 2.4.8-p5, MSI, Vnecoms, City Manager and Delivery Availability. Preview, checkout charging and catalog filtering are separate flags, disabled by default. Installing the module is not approval to activate delivery coverage or rates.

## Implemented code

- Deterministic source allocation, vendor defaults, per-SKU mode and country/city/locality overrides, direct and common-hub plans. Empty modes/coverage mean unavailable. Missing rates never mean free shipping.
- Integer minor-unit origin/destination rates: base, per unit and per started kilogram. Revenue recipient and carrier-cost bearer are independent. Hub inbound charges remain separate; consolidated last-mile charge occurs once.
- Checkout carrier, actual-address/quantity/rate revalidation, serialized allocation through order placement, immutable order snapshots and source holds alongside core MSI reservations. Core MSI remains responsible for actual inventory deduction. Shipment, cancellation and unshipped-refund holds are reconciled without creating duplicate MSI reservations.
- Shipping tax uses the policy's net-price basis and Magento's tax jurisdiction/class calculation. Invoice/refund adapters prevent marketplace shipping being omitted or duplicated by Vnecoms vendor shipping.
- Paid invoice/refund shipping subledger, actual carrier bills, bank/COD receipt reconciliation and recording completed vendor shipping payouts. Replay-safe events and balances prevent duplicate entries and excessive payouts. These records do not transfer money.
- Stock/coverage-aware catalog filtering before search counts and pagination. Area-specific app queries use POST and a separate cache. Storefront URLs carry destination parameters, so full-page cache varies by area; selected-area browsing uses OpenSearch instead of unfiltered Algolia. Product widgets receive the same eligibility set.
- Vendor owner-only policy screen, per-product area dropdowns, existing vendor order-page responsibility and courier/tracking controls. Marketplace groups allow the vendor to report ready for pickup. Vendor shipping reports are separate from confirmed inventory shipments.
- Admin order/group progress and shipping finance workspace, ACL-protected configuration, finance and fleet permissions.
- Authenticated customer order progress and vendor REST endpoints. Fleet contract and responsibilities: see FLEET-CONTRACT.md. Legacy orders remain unclassified until an allowed explicit selection; no allocation, fee, weight or address pin is invented.
- Customer Flutter branch: cart direct/hub previews, area-scoped catalog/search, order delivery progress. Vendor Flutter branch: fulfillment defaults and SKU coverage. App deployment is separate from Magento installation.

## Configuration

Existing MSI source codes must be mapped to real owners and active City Manager locations. Policy JSON is managed at Stores > Configuration > Hub Fulfillment; vendor owners can manage their own defaults and products from Fulfillment. Sources and rate configuration currently use the administrator policy editor, not a dedicated warehouse/rate grid.

Policy version 1: currency (actual base currency), minor_digits=2, sources[], vendors[], products[], rates[]. Sources contain code, vendor_id, kind=vendor|hub, priority, location={country,city_id,locality_id}. Vendors contain vendor_id and modes=[vendor|marketplace|hub]. Products contain sku, vendor_id, modes and optional coverage[]; omitted coverage inherits configured routes, an empty array disables delivery. Each rate identifies source, leg=direct|inbound|outbound, mode, destination, base_minor/per_unit_minor/per_kg_minor, cost_base_minor/cost_per_unit_minor/cost_per_kg_minor, revenue_owner and cost_owner. Inbound rates also identify the destination hub. Outbound hub revenue/cost belongs to the marketplace.

Test/fixtures.php is entirely synthetic. Never import it as real coverage. Existing Magento/Vnecoms table rates are preserved; they must not be converted from weight/postcode bands into this rate model without an explicit mapping.

Preview GET /hubfulfillment/quote/index accepts country, region, city, locality, strategy and items JSON [{sku,qty_milli}]. Only purchased physical simple SKUs are supported; configurable checkout children are expanded. Bundles/grouped fulfillment and Magento multishipping are not enabled. A preview is not a reservation or final taxed checkout total.

## Financial boundary

Vnecoms remains authoritative for merchandise and commission. This module accounts for shipping separately, preventing duplicate Vnecoms shipping credits. Vendor payable is not vendor profit: cost of goods, payment fees and operating costs are not known. Settlement::project is a pure estimate; Journal/Finance are actual shipping event records. Payout recording requires collected-shipping reconciliation and a completed bank reference. Carrier booking, automatic bank transfers, payment-provider reconciliation and Odoo posting require separate integrations and acceptance tests.

## Verification and release gates

- Test/run.php: 39 deterministic policy/allocation/rate/accounting cases.
- Test/lifecycle.php: 28 isolated synthetic MySQL cases, including dispatch role isolation, optimistic versions, replay, invoice/refund, COD/bank receipt, payout, shipment and partial cancellation/refund holds.
- Test/runtime.php: 24 read-only installed Magento compatibility checks, real MSI product eligibility, positive/negative OpenSearch pagination, XML and a real legacy order contract. Test scripts target the explicit Hub Market review environment; inspect paths before reuse elsewhere.
- Separate app repositories contain service/widget tests; native device/store-release acceptance is a separate gate.

Full checkout and browser acceptance, native device acceptance, configured-source concurrency, real rate imports, labels/payment/Odoo/Fleet end-to-end checks are not implied by these suites. Never mark their ClickUp cases passed without evidence.

Deployment must use a database/configuration backup and an allowlisted declarative schema diff: three me_fulfillment_* tables plus sales_order.hf_plan_json. Do not run blanket setup:upgrade on the current shared installation; unrelated schema drift exists. Compile dependencies and verify the disabled flags and legacy storefront before activating any live allocation or pricing.