# Odoo Connector — Setup & Operations Runbook

> **Module:** `MagentoEgypt_OdooConnector` · **Target:** Magento 2.4.6, PHP 8.1 · **Date:** 2026-06-02
> Companion to [`architecture.md`](architecture.md). This file covers **enabling**, **configuring**, and the **cron** that drives sync.

> ⚠️ **Use `php8.1` for every CLI command on this server.** The default `php` is 7.4 and Magento 2.4.6 will refuse to run on it. All examples below use `php8.1`.

---

## 1. Enable the module (per environment)

`app/etc/config.php` is git-ignored, so enabling is **local to each environment** — repeat these on staging/production.

```bash
php8.1 bin/magento module:enable MagentoEgypt_OdooConnector
php8.1 bin/magento setup:upgrade          # creates the 3 sync tables, registers routes/cron/observers
php8.1 bin/magento cache:flush
# Production mode only (see §5):
php8.1 bin/magento setup:di:compile
php8.1 bin/magento setup:static-content:deploy
```

Verify:

```bash
php8.1 bin/magento module:status MagentoEgypt_OdooConnector   # -> "Module is enabled"
php8.1 bin/magento odoo:status                                 # prints map/queue/log row counts
```

---

## 2. Configure

**Admin path:** `Stores → Configuration → Magento Egypt → Odoo Connector`
(ACL resources `MagentoEgypt_OdooConnector::config` and `::monitor` — grant these to non-Administrator roles.)

Config is **store-scoped**; set the API key and Company ID per website for multi-company / multi-website setups.

### Connection (`odooconnector/connection/*`)

| Field | Config path | Notes |
|---|---|---|
| Odoo Base URL | `connection/odoo_url` | e.g. `https://erp.example.com` (no trailing slash) |
| Database Name | `connection/odoo_db` | Odoo database |
| API User (login) | `connection/api_user` | Dedicated integration user (consumes an Enterprise seat) |
| API Key | `connection/api_key` | **Encrypted.** Odoo *API Key*, not the password |
| Protocol | `connection/auth_protocol` | `jsonrpc` (recommended) or `xmlrpc` |
| Odoo Company ID | `connection/odoo_company_id` | Numeric `res.company` id; set per website, blank = Odoo default |
| Inbound Shared Secret | `connection/inbound_shared_secret` | **Encrypted.** HMAC key verifying the `X-Odoo-Signature` header on inbound calls |

After saving, click **Test Connection** in the same group (it reads the *saved* values).

### Domains & conflict rules (`odooconnector/domains/*`)

| Field | Path | Values |
|---|---|---|
| Enable Products / Customers / Orders / Inventory / Reports | `enable_products`, `enable_customers`, `enable_orders`, `enable_inventory`, `enable_reports` | Yes/No — nothing syncs for a domain that is Off |
| Conflict rule (products / customers / inventory) | `conflict_rule_products`, `conflict_rule_customers`, `conflict_rule_inventory` | `magento_wins` · `odoo_wins` · `last_write_wins` · `field_level` |
| Product sale-price owner | `product_price_owner` | `magento` (default) · `odoo` |
| Order granularity | `order_granularity` | `per_vendor` (default) · `master` |

### Processing (`odooconnector/processing/*`)

| Field | Path | Default |
|---|---|---|
| Queue batch size | `batch_size` | 50 |
| Max retry attempts | `max_attempts` | 5 |
| Retry backoff base (min) | `backoff_base_minutes` | 1 — next retry at `base × 2^attempts` (capped) |
| Enable scheduled pull (Odoo→Magento) | `pull_enabled` | watermark catch-all in addition to Odoo push |

### Logging (`odooconnector/logging/*`)

| Field | Path | Values |
|---|---|---|
| Log level | `log_level` | `OFF` · `ERROR` (default) · `INFO` · `DEBUG` → writes `var/log/odoo_sync.log` + the sync-log table |
| Inbound Callback URL | (display only) | The URL to register in Odoo for inbound push |

### Scriptable alternative (CLI)

Non-secret fields can be set without the UI; obscure fields are encrypted automatically on save:

```bash
php8.1 bin/magento config:set odooconnector/connection/odoo_url   "https://erp.example.com"
php8.1 bin/magento config:set odooconnector/connection/odoo_db    "erp_prod"
php8.1 bin/magento config:set odooconnector/connection/api_user   "magento_integration"
php8.1 bin/magento config:set odooconnector/connection/api_key    "<odoo-api-key>"   # encrypted on save
php8.1 bin/magento config:set odooconnector/domains/enable_products 1
# per-website scope:
php8.1 bin/magento config:set --scope=websites --scope-code=base odooconnector/connection/odoo_company_id 3
php8.1 bin/magento cache:flush
```

---

## 3. Cron (drives all async sync)

Two jobs are declared in `etc/crontab.xml` (group `default`):

| Job | Schedule | Class | Does |
|---|---|---|---|
| `magentoegypt_odoo_sync_consumer` | `* * * * *` (every min) | `Cron\RunConsumer` → `Queue\Consumer::run()` | Claims due queue rows, dispatches to Odoo, applies retry/backoff |
| `magentoegypt_odoo_reconcile` | `*/30 * * * *` | `Cron\RunReconcile` → `Reconcile\ReconciliationService::run()` | Re-drives failed rows + re-enqueues linked entities to heal drift |

These only run if **Magento's own cron** is running. Install/verify it (uses `php8.1`):

```bash
php8.1 bin/magento cron:install            # adds the cron:run line to the user crontab (idempotent)
crontab -l | grep multi.magento2.click     # confirm: * * * * * /usr/bin/php8.1 .../bin/magento cron:run ...
```

> This server already runs `cron:run` every minute for this install. There is a **second, unrelated Magento** at `/var/www/zoonze` with its own crontab block — don't disturb it; `cron:install` only rewrites this install's block.

Verify jobs are scheduled and executing:

```bash
php8.1 bin/magento cron:run --group=default
# inspect the schedule table:
php8.1 -r 'require "app/bootstrap.php"; ...' # or check via DB: SELECT job_code,status,scheduled_at FROM cron_schedule WHERE job_code LIKE "magentoegypt_odoo%" ORDER BY schedule_id DESC LIMIT 10;
```

Drain the queue manually (bypasses cron, useful for testing):

```bash
php8.1 bin/magento odoo:queue:run
php8.1 bin/magento odoo:reconcile
```

---

## 4. First run — link existing data

Pulls are **read-only** on Magento (they only populate the entity-map table), so they're safe to run first to establish ID mappings before two-way sync:

```bash
php8.1 bin/magento odoo:products:pull      # link Odoo products  -> entity map
php8.1 bin/magento odoo:customers:pull     # link Odoo customers -> entity map
php8.1 bin/magento odoo:status             # confirm map row counts climbed
```

Then push / let cron take over:

```bash
php8.1 bin/magento odoo:products:push
php8.1 bin/magento odoo:customers:push
php8.1 bin/magento odoo:inventory:push
php8.1 bin/magento odoo:orders:push
php8.1 bin/magento odoo:report             # consistency report (read-only)
php8.1 bin/magento odoo:report:analytics   # Magento vs Odoo sales/revenue/inventory
```

Admin monitoring grids: **Magento Egypt** menu → *Entity Map*, *Sync Queue*, *Sync Log*.

---

## 5. Deploying to production mode

Developer mode generates DI/interceptors on demand; production mode needs them precompiled:

```bash
php8.1 bin/magento deploy:mode:set production   # runs di:compile + static-content:deploy
# or, staying in current mode, just:
php8.1 bin/magento setup:di:compile
php8.1 bin/magento cache:flush
```

After any change to `di.xml`, observers, plugins, or `db_schema.xml`, re-run `setup:upgrade` (schema) and, in production, `setup:di:compile`.
