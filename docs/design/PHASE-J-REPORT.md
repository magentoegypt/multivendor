# Phase J — build, deploy, QA, log review

## What was run, and what was deliberately not

| Step | Action | Why |
|---|---|---|
| `setup:upgrade` | **skipped** | `setup:db:status` → "All modules are up to date". On a production site a no-op that can drop into maintenance mode is pure downside. |
| `setup:di:compile` | **skipped** | The theme is **1 PHP file** (`registration.php`) and **0 `di.xml`**. Nothing to compile. Running it means clearing `generated/` on a live site — real risk, zero benefit. |
| `static-content:deploy` | **run, scoped** | `-t MagentoEgypt/hub-market -f --jobs 2`, `en_US ar_SA`. |
| `cache:flush` | run | |

The deploy was **scoped to the new theme**. `pub/static/frontend/Mgs/supro/` still carries its
2026-08-01 mtime — the live theme's static content was never rewritten.

Preceded by a clean-out of `pub/static/frontend/MagentoEgypt/hub-market` and both
`var/view_preprocessed` trees, so the LESS compiled from scratch rather than from cache.

```
exit=0, 46.8s, 3344 files per locale, zero LESS errors or warnings
```

## Build verification

| Check | Result |
|---|---|
| `styles-m.css` / `styles-l.css` | 464 KB / 142 KB, both locales |
| Token layer emitted | `--hm-accent`, `--hm-accent-strong`, `--hm-font-sans`, `hm-btn--primary`, `hm-nav__grid`, `hm-header` all present |
| Fonts deployed | 18 woff2 (12 ours + blank's inherited) |
| Icon sprite | `hm-icons.svg` 12,109 bytes; **40 `<symbol>`** inlined, 11 `<use>` on home |
| Theme layout XML | `validate-theme-xml.sh` → all well-formed |

## Page QA — 11 URLs, both locales

Every page returns the expected status on the new theme, with the header, skip link
and inline sprite present.

| | |
|---|---|
| `/en/` `/ar/` `/en/gear.html` `/en/sellerlist` `/en/bundles` | 200 |
| `/en/checkout/cart/` `/en/customer/account/login/` `/en/about-us` | 200 |
| `/ar/sellerlist` `/ar/bundles` | 200 |
| `/en/no-such-page-xyz` | **404** (correct) |

Stylesheets holding at **27** (down from 41 pre-Phase-I), of which 26 are the theme's.
**Cairo: 0 occurrences** — the MGS typography override has not crept back.

## Log review — nothing found is theme-caused

Three classes of noise showed up. All three predate this work; I verified each rather
than assuming.

### 1. `Invalid template file: 'widget/owl_banner.phtml'` (MGS_Fbuilder) — pre-existing

155 occurrences. **First one is 2026-06-24**, long before the theme existed. I also
measured it directly: flush cache, request `/en/` and `/ar/` under both the live Supro
UA and the preview UA, count new lines. **Zero new lines from either theme.** The
templates genuinely do not exist on disk while `MGS_Fbuilder` is enabled.

### 2. `/media/styles.css` returns 403 on every page load — pre-existing, admin setting

Not a theme file and not in any layout. It comes from the database:

```
core_config_data → design/head/includes @ default/0
<link rel="stylesheet" type="text/css" media="all" href="{{MEDIA_URL}}styles.css" />
```

`pub/media/styles.css` does not exist. The live Supro theme links it too, so every page
on the site has been firing a 403 for this. Fixable in **Content → Design → Configuration
→ HTML Head → Scripts and Style Sheets** — a store-wide setting, so left alone here.

### 3. `NoNodesAvailableException` ×6 at 11:08–11:10 — transient, resolved

OpenSearch is a **Docker** container (there is no `opensearch` systemd unit on this box,
which is why a service check looks like it is dead). Its process start time is
`11:08:17` — exactly when the exceptions begin. Magento logged six failures across the
container's startup window and recovered.

Search verified working afterwards: `?q=bag` → 25 results, `?q=shirt` → 7.

**Cluster status is red, and that is not ours.** It is a shared single-node cluster —
`erpnext_*`, `zoonze_*`, `design1_*`, `locafy_248_*` sit alongside `magento2_*`. The red
primaries belong to those other tenants. Our three indices are **yellow**, which is the
expected state for a one-node cluster with replicas configured: the primary is assigned,
the replica cannot be.

| Index | Store | State | Docs |
|---|---|---|---|
| `magento2_product_3_v37` | 3 — English | yellow | 316 |
| `magento2_product_1_v40` | 1 — عربي | yellow | 316 |
| `magento2_product_2_v33` | 2 — Vendor Panel | yellow | 0 |

`magento2_product_11/12/13_v4` are **not ours** — this install has only store ids 0–3.

Nothing was written to `system.log` or `exception.log` by the build itself.

## Repo state — nothing committed

| | |
|---|---|
| New, untracked | `app/design/frontend/MagentoEgypt/` (788 K), `docs/` (856 K) |
| Modified, tracked | 2 files under `Mgs/supro` — **mtime 2026-08-01**, predate this work, not mine |

The live theme is still `Mgs/supro`. The new theme remains reachable only through the
`design/theme/ua_regexp` preview rule (`/HubMarketPreview/` → theme 11), which is a row
in the live database and should be removed when it is no longer needed.

## Still open (carried from Phase I)

- `aria-live` is 0 everywhere — cart and filter updates are silent to screen readers.
- No `<h1>` on the vendor microsite or the 404.
- 118 contrast failures remain, concentrated in PDP (48) and microsite (40), largely
  third-party Vnecoms markup.
- `<html lang="ar">` ships **without `dir`**; RTL is driven by `html:lang(ar)` in CSS.
  Setting the attribute properly would be more robust.
- Deferred by agreement: Phase K (seller panel, `vendors` area), Phase L (MGS decommission).
