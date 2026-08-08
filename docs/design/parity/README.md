# Design parity harness

Cross-verifies the Magento storefront against the Figma reference build, at every
phase rather than only at the end.

- **Reference** — `https://doze-coyote-58038022.figma.site/`
- **Target** — `https://hub-market.magento2.click/`

## Why a census rather than a diff

The two sides share no DOM. Element-by-element diffing would be noise. Instead
[harness.js](harness.js) reports what each page **actually renders** — the colour
set, type scale, radii, role probes and accessibility signals — and
[compare.py](compare.py) reports the drift between the two censuses and gates on
the things that must not regress.

This also catches what a stylesheet audit cannot: the Figma build declares a full
token layer that almost nothing consumes, so reading its CSS overstates how
consistent it is. The census reads the computed styles.

## Running it

The storefront **403s from the application server** — the WAF blocks its own
host. All verification therefore runs from a developer browser via the Chrome
MCP. This is the only supported channel.

1. Open the reference page, paste the contents of `harness.js` into the Chrome
   MCP `javascript_tool`, and save the returned JSON as
   `docs/design/parity/<page>/reference.json`.
2. Repeat on the equivalent target page → `target.json`.
3. Compare:

```bash
python3 docs/design/parity/compare.py docs/design/parity/home/reference.json docs/design/parity/home/target.json
```

Exit code is `1` if any gate fails, so a phase can be gated on it.

Repeat per page at **1440 / 768 / 390** px, in **both** LTR and RTL. A phase is
not done until its parity log is clean.

### Payload size

`harness.js` is deliberately compact — the MCP `javascript_tool` truncates large
returns, and a truncated census silently looks like *fewer* colours, which would
turn a failing page into a passing one. Raise the `top()` caps only when
inspecting a single page in isolation, never for a gated run.

## Gates

| Gate | Requirement |
|---|---|
| Off-palette colours | zero colours outside `docs/design/tokens.css` |
| Fonts | only DM Sans, Playfair Display, IBM Plex Sans Arabic |
| Type floor | nothing below 14px |
| Contrast | zero WCAG AA failures |
| `aria-label` | > 0 |
| `label[for]` | > 0 |
| `<h1>` | exactly 1 |
| `img` without `alt` | 0 |
| Skip link | present |

## Reference baseline — captured 2026-08-05

Run against the Figma build's homepage at 1536×695, LTR. This is what the target
must *beat*, not match:

| Signal | Reference |
|---|---|
| `aria-label` | **0** |
| `aria-live` | **0** |
| `label[for]` | **0** |
| `<bdi>` / `[dir]` | **0** |
| Images lazy-loaded | **0 of 77** |
| Skip link | **absent** |
| Gradients | 20 |
| Nodes / text nodes | 2027 / 465 |

Rendered colour census confirmed the off-palette findings from live paint, not
from stylesheet reading:

| Colour | Uses | Verdict |
|---|---|---|
| `#1a1a2e` foreground | 641 | on-palette |
| amber `oklch(.828 .189 84.429)` | 352 | off-palette (rating stars) |
| `#ffffff` | 311 | on-palette |
| `#f26522` accent | 110 | on-palette |
| `#0f2144` navy | 73 | on-palette |
| **`#6366f1` indigo** | 10 | **off-brand** |
| **`#ec4899` pink** | 7 | **off-brand** |
| **`#2d7a3a` green** | 7 | **off-brand** |

Images do carry `alt` (0 missing) — the audit did not claim otherwise, but it is
worth recording as a thing that already passes.

## Adding a role probe

`ROLES` in `harness.js` maps a visual role to a list of candidate selectors; the
first match wins, so one entry covers both sides. Extend it as each phase adds
components — e.g. the vendor card in Phase F, the OSC summary in Phase G.
