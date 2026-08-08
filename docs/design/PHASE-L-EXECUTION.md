# Phase L — execution log

Phase L was blocked until the theme switch. It is now unblocked:
`design/theme/theme_id @ default/0 = 11` and the `ua_regexp` preview rule is gone.

## The thing to understand before reading the waves

**Decommissioning MGS burns the rollback path.** `theme_id 7` only gives a working
Supro site while the modules Supro renders through are still enabled. Once
`MGS_Fbuilder`, `MGS_SuproTheme`, `MGS_ThemeSettings` and `MGS_Mmegamenu` are
disabled, reverting the theme yields a broken storefront, not the old one.

So the waves are ordered by how one-way they are, not just by risk of breakage:

| | Reverses cleanly? | |
|---|---|---|
| Wave 1–2 | **yes** | unused, no data, Supro never rendered them meaningfully |
| Wave 3–4 | no, in practice | Supro's chrome and page builder |
| Wave 5 | yes | behavioural add-ons |
| Wave 6 | **no — data** | Brand and Blog own content with no replacement |

Disabling a module never drops its tables. `mgs_lookbook` still holds its 1 row
after wave 1–2; re-enabling restores the feature intact. Nothing in waves 1–5
destroys data. Only wave 6 would, and only if the data is then deleted.

## Preconditions, checked

| | |
|---|---|
| Hub Market live | `theme_id = 11`, verified on 13 URLs both locales |
| Preview rule removed | `design/theme/*` is a single row |
| Verified DB backup | `multi_vendor_m2-pre-theme-switch-20260806-131546.sql.gz`, 556/556 tables, row counts matched |
| Hard dependencies between MGS modules | **none** — no `<sequence>` or `<depends>` anywhere references them |

That last one matters: `module:disable` refuses when another enabled module depends
on the target, and nothing does, so the waves can be ordered freely.

## Cost per wave

`bin/magento module:disable` clears `generated/` on this install, so **every wave
costs a `setup:di:compile` (~3 min)** in production mode, during which the site
rebuilds DI per request and the first few requests are slow. That is why modules
are batched into waves rather than disabled one at a time.

## Wave 1+2 — MGS_Portfolio, MGS_Lookbook, MGS_Testimonial, MGS_ExportBlock

Chosen because they are genuinely inert, not merely unused-looking:

| Module | Data | Frontend layouts | Events | Plugins |
|---|---|---|---|---|
| `MGS_Portfolio` | 4 tables, **all empty** | 2 | 0 | 0 |
| `MGS_Lookbook` | 1 row | 2 | 0 | 0 |
| `MGS_Testimonial` | no tables | 0 | 0 | 0 |
| `MGS_ExportBlock` | no tables | 0 | 0 | 0 |

`MGS_Lookbook` was the one worth removing on its own merits: it loaded **two
stylesheets plus inline JS on every page of the site** —
`MGS_Lookbook/css/theme.default.min.css` and `MGS_Lookbook/css/styles.css` — for a
pin-annotation feature used by exactly one retired demo page (`home-shoppable`).

Baseline before the wave: **27 stylesheets** on `/en/`.
