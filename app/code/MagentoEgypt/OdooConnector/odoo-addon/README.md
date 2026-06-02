# magentoegypt_connector (Odoo 16 addon — Odoo → Magento push side)

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
  the master here); unmapped keys are skipped. Product name/price is handled
  today; customer/order inbound handlers can be extended on the Magento side.
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
