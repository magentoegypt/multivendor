# magentoegypt_connector (Odoo 19 addon — Odoo → Magento push side)

Companion to the Magento module `MagentoEgypt_OdooConnector`. It pushes Odoo
changes to Magento's inbound endpoint (`/odooconnector/inbound/receive`),
closing the bidirectional loop.

## How it works
1. **Automated actions** on `product.template`, `res.partner`, `sale.order`
   (create + write) call `magentoegypt.sync.outbox.enqueue(...)`. Changes made
   *by Magento* carry the `magento_sync` context and are skipped (no echo).
2. The **outbox** model queues an envelope per change
   (`{entity_type, operation, natural_key, payload, checksum, correlation_id}`).
3. A **scheduled action** (every minute) drains pending rows: it HMAC-SHA256-signs
   the JSON body with the shared secret and `POST`s it with header
   `X-Odoo-Signature` to `<magento_url>/odooconnector/inbound/receive`.

## Synced Fields

What data moves between Magento and Odoo, per domain and direction. Two parts:
**Mapped today** (the live contract) and **Roadmap** (planned, not yet implemented).

### Mapped today

#### Products — `product.template` (natural key: SKU = `default_code`)

**Magento → Odoo (push)** — `ProductPushMapper` + `ProductPusher`

| Magento source | Odoo field | Transform / condition |
|---|---|---|
| `name` | `name` | always |
| `sku` | `default_code` | always — natural key |
| `price` | `list_price` | always (float) |
| — | `type` | constant `'consu'` (Odoo 18+ Goods) |
| — | `is_storable` | constant `true` (keeps stock-tracked) |
| `status` (1/2) | `sale_ok` | 1→true, else false |
| `visibility` (1–4) | `x_magento_visibility` | int passthrough |
| `description` | `description_sale` | only if set & non-empty |
| `special_price` | `x_magento_special_price` | only if set, non-empty, numeric |
| `cost` | `standard_price` | from the `cost` attribute, if numeric |
| `weight` | `weight` | if set (> 0) |
| `barcode` | `barcode` | from a `barcode` attribute, if present (Odoo enforces uniqueness) |
| `short_description` | `x_magento_short_description` | if set & non-empty |
| `meta_title`/`meta_description`/`meta_keyword` | `website_meta_title`/`_description`/`_keywords` | if set (needs Odoo website module) |
| extra gallery images | `product_template_image_ids` | up to 5, replace-then-add; excludes the main image |
| primary `category_ids` | `categ_id` | resolved via `CategoryResolver`; only if one resolves |
| curated custom attrs | `x_magento_attributes` | JSON; excludes a skip-list (price/name/sku/status/image/…); scalar non-empty only |
| main image file | `image_1920` | base64 of `catalog/product<image>`; only if file exists; push-only, excluded from echo checksum |
| website→company | `company_id` | int when websites agree on one company; else `false` (global/shared) |
| configurable products | variant `product.template` | super-attributes → `attribute_line_ids`; children → variants (`VariantPusher`); child SKU → variant `default_code`; standalone child templates archived; simple children skipped on push |

**Odoo → Magento** — outbox (`sync_outbox`, entity `product`) → `InboundProcessor` (update-only on SKU match)

| Odoo source | Payload key | Applied to Magento | Condition |
|---|---|---|---|
| `default_code` | *(natural key)* | SKU match | always |
| `name` | `name` | `setName()` | if non-empty |
| `list_price` | `price` | `setPrice()` | if numeric |
| `description_sale` | `description` | `description` attr | if non-empty |
| `sale_ok` | `status` | `setStatus()` (1/2) | if 1 or 2 |
| `x_magento_visibility` | `visibility` | `setVisibility()` | if > 0 |
| `x_magento_special_price` | `special_price` | `special_price` attr | if numeric > 0 |
| `standard_price` | `cost` | `cost` attr | if numeric > 0 |
| `weight` | `weight` | `setWeight()` | if numeric > 0 |
| `barcode` | `barcode` | `barcode` attr | if non-empty |
| `x_magento_short_description` | `short_description` | `short_description` attr | if non-empty |
| `image_1920` | `image` | media image (`applyImage`) | if non-empty |
| `categ_id.name` | `categories` | find/create + assign (`applyCategories`) | if non-empty |

*Pull command reads `id, default_code, name, list_price, barcode, type, write_date`; echo/conflict checksum = `default_code, name, list_price, barcode, type`.*

#### Customers — `res.partner` (natural key: lowercased email)

**Magento → Odoo (push)** — `CustomerPushMapper` + `CustomerPusher` (main partner)

| Magento source | Odoo field | Transform / condition |
|---|---|---|
| `prefix`+`firstname`+`middlename`+`lastname`+`suffix` | `name` | trimmed concat of present parts; falls back to email |
| `email` | `email` | always |
| — | `customer_rank` | constant `1` |
| billing `telephone` | `phone` | if non-empty |
| billing `street[0]` | `street` | if non-empty |
| billing `street[1]` | `street2` | if present |
| billing `city` | `city` | if set |
| billing `postcode` | `zip` | if set |
| billing `country_id` (ISO) | `country_id` | resolved to `res.country` id; only if found |
| billing `region` (code) | `state_id` | resolved to `res.country.state` within the country; only if found |
| `taxvat` | `vat` | if non-empty |
| billing `company` | `company_name` | if non-empty (partner kept individual) |
| `mobile` attr | `x_magento_mobile` | from a `mobile` customer attribute, if present |
| `dob` | `x_magento_dob` | if set |
| `gender` (1/2/3) | `x_magento_gender` | mapped to Male / Female / Not Specified |
| customer group code | `x_magento_customer_group` | raw code |
| customer group code | `category_id` | m2m tag `"Magento: <code>"` (find/create); only if resolves |
| store→company | `company_id` | only if configured |

**Shipping address → child `res.partner`** (`upsertShippingChild`, only if a default shipping address exists): `name` (concat, defaults "Shipping Address"), `phone`, `street`, `street2`, `city`, `zip`, `country_id`/`state_id` (same address logic), plus constants `type='delivery'` and `parent_id` = customer.

**Odoo → Magento** — outbox (entity `customer_buyer`) → `InboundProcessor`

| Odoo source | Payload key | Applied to Magento | Condition |
|---|---|---|---|
| `email` | *(natural key)* | email match | always |
| `name` | `name` | `setFirstname`/`setLastname` (split on first space) | if non-empty |

*Phone/address are **not** sent back O→M (outbox carries only name+email). Pull reads `id, name, email, phone, customer_rank, write_date` (filters `customer_rank>0`); checksum = email, name, phone.*

#### Orders — `sale.order` (natural key: `increment_id` → `client_order_ref`; Magento-authoritative, create-once)

**Magento → Odoo (push)** — `OrderPusher` — header

| Magento source | Odoo field | Transform / condition |
|---|---|---|
| `increment_id` | `client_order_ref` | natural key; on create |
| resolved partner | `partner_id` | find res.partner by `customer_email`, else create; on create |
| line items | `order_line` | see below; on create |
| store→company | `company_id` | only if configured; on create |
| `created_at` | `date_order` | on create |
| `order_currency_code` | `pricelist_id` → `currency_id` | per-currency pricelist (find/create `Magento <CUR>`) so the order currency sticks; falls back to `currency_id` if `pricelist_id` is unavailable; on create |
| `customer_note` | `note` | if non-empty; on create |
| billing address | `partner_invoice_id` | invoice child contact under the partner; on create |
| shipping address | `partner_shipping_id` | delivery child contact under the partner; on create |
| store / website | `team_id` | single "Magento" sales team (find/create); on create |
| `status` | `x_magento_status` | every push |
| `shipping_description` / `shipping_method` | `x_magento_shipping_method` | if non-empty |
| payment method code | `x_magento_payment_method` | if payment exists |
| `discount_amount` (abs) | `x_magento_discount_amount` | if > 0 |
| `shipping_amount` | `x_magento_shipping_amount` + a Shipping `order_line` | if > 0 — also added as a line (find/create `MAGENTO_SHIPPING` service product) so Odoo totals include shipping |
| first shipment track # | `x_magento_tracking` | if a track exists |
| Magento state | `state` | processing/complete/closed→`action_confirm`; canceled→`action_cancel` |

**Line items** (`sale.order.line`, per item with `parent_item_id` null):

| Magento source | Odoo field | Transform / condition |
|---|---|---|
| `sku` | `product_id` | resolved via `product.product`.`default_code`; cascade-creates product if missing; line skipped if unresolvable |
| `qty_ordered` | `product_uom_qty` | float |
| `price` | `price_unit` | float |
| `name` | `name` | item name |
| — | `tax_ids` | cleared `[[6,0,[]]]` — Odoo 19 field (renamed from `tax_id`); Magento is tax authority |
| `discount_percent` | `discount` | if > 0 |

*Invoiced orders cascade to Odoo `account.move` (out_invoice) via `OrderDocuments::syncInvoices`; credit memos → `account.move` (out_refund) via `syncCreditmemos`; shipment tracking → the delivery picking's `carrier_tracking_ref` via `syncShipmentTracking` (pickings are not auto-validated). All best-effort.*

**Odoo → Magento** — outbox (entity `order`) → `InboundProcessor`, **additive only** (never overwrites the Magento order)

| Odoo source | Payload key | Applied to Magento | Condition |
|---|---|---|---|
| `client_order_ref`/`name` | *(natural key)* | increment_id match | always |
| `state` | `state` | status-history comment "Odoo state: …" | if non-empty |
| `x_magento_tracking` | `tracking` | status-history comment "Odoo tracking: …" | if non-empty |
| `amount_total` | `amount_total` | *(checksum only — not applied)* | — |

**Odoo custom fields defined by the addon** (all others above are standard Odoo fields):

- `product.template`: `x_magento_visibility` (Int), `x_magento_special_price` (Float), `x_magento_attributes` (Text/JSON), `x_magento_short_description` (Text)
- `res.partner`: `x_magento_customer_group`, `x_magento_mobile`, `x_magento_dob`, `x_magento_gender` (all Char)
- `sale.order`: `x_magento_status`, `x_magento_shipping_method`, `x_magento_payment_method`, `x_magento_discount_amount`, `x_magento_shipping_amount`, `x_magento_tracking`

### Roadmap — not yet mapped

> ⚠️ **Planned, not implemented.** The fields below do **not** sync today — they are
> candidates for future work. Direction is Magento→Odoo unless noted.

#### Products (→ product.template)

| Magento source | Odoo target | Why / note |
|---|---|---|
| `tax_class_id` | `taxes_id` | product tax mapping; not yet mapped |
| tier prices | pricelist items | volume / customer-group pricing |
| `uom` / dimensions | `uom_id` / `uom_po_id` | unit of measure; today defaults |

#### Customers (→ res.partner)

| Magento source | Odoo target | Why / note |
|---|---|---|
| billing `company` | `is_company` / dedicated company partner | today stored as free-text `company_name`; not promoted to a company-type partner |
| billing address | `type='invoice'` child partner | mirror the existing shipping (`type='delivery'`) child pattern |
| **O→M:** `phone`, address | back to Magento customer | O→M today carries only name+email; inbound applies only name |

#### Orders (→ sale.order / sale.order.line)

| Magento source | Odoo target | Why / note |
|---|---|---|
| per-line `tax_percent` / `tax_amount` | real `tax_ids` | Odoo line tax is cleared (`tax_ids`, the Odoo 19 field; Magento is the authority); mapping Magento's actual per-rate taxes onto Odoo is the follow-up |
| shipments (full) | `stock.picking` validation | tracking now synced to the picking's `carrier_tracking_ref`; auto-validating delivery (transfer/backorder wizards over RPC) is still deferred |
| `coupon_code` | coupon / promotion | discount provenance |

## Install
1. Copy `magentoegypt_connector/` into your Odoo addons path.
2. `Apps → Update Apps List`, then install **MagentoEgypt Odoo Connector**
   (or `odoo-bin -u magentoegypt_connector -d <db>`).
3. Set **System Parameters** (Settings → Technical → System Parameters):
   - `magentoegypt.magento_url` = `https://brassandwood.org`
   - `magentoegypt.hmac_secret` = the **same** value as the Magento config
     *Stores → Config → Magento Egypt → Odoo Connector → Inbound Shared Secret*
   - `magentoegypt.enabled` = `1`
4. The cron **“MagentoEgypt: drain sync outbox”** runs every minute (Settings →
   Technical → Scheduled Actions). Ensure the Odoo cron worker is running.

## Notes / contract
- **Loop prevention** is primarily the `magento_sync` context flag (set by the
  Magento `OdooClient` on every M→O call) — the addon skips those. The checksum
  additionally suppresses duplicate delivery of the same Odoo envelope.
- Magento applies inbound **update-only to already-mapped records** (Magento is
  the master here); unmapped keys are skipped. Inbound today updates product
  `name`/`price`/`description`/`status`/`visibility`/`special_price`/image/categories,
  the customer name, and appends order state/tracking notes (orders stay
  Magento-authoritative). See **Synced Fields** above for the full per-field contract.
- The HMAC secret must match on both sides. The endpoint returns `401` on a bad
  signature, `200` with an outcome JSON otherwise.

## Test (after install + config)
```python
# Odoo shell
env['ir.config_parameter'].sudo().set_param('magentoegypt.enabled', '1')
p = env['product.template'].search([('default_code','!=',False)], limit=1)
p.name = p.name + ' (odoo edit)'   # automated action enqueues
env['magentoegypt.sync.outbox']._cron_drain()   # pushes now
# -> Magento product (if mapped by SKU) updates; check magentoegypt_odoo_sync_log (direction o2m)
```
