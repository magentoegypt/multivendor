# Figma Make — paste sequence

Paste these into the Figma Make chat **in order**, one at a time, letting the build
settle between each. Prompt 1 carries the shared context, so start there even if you
intend to skip ahead.

Full reasoning and evidence: [../figma-make-revision-brief.md](../figma-make-revision-brief.md)

| # | Prompt | Size | Blocking? |
|---|---|---|---|
| 1 | [Replace the token layer ⚠️](1-tokens.md) | 15 KB | yes — everything else assumes the new tokens |
| 2 | [Accessibility P0 ⚠️](2-accessibility.md) | 2 KB | yes |
| 3 | [Arabic and RTL P0 ⚠️](3-rtl-arabic.md) | 3 KB | yes |
| 4 | [Currency and locale P0](4-currency-locale.md) | 1 KB | yes |
| 5 | [Consolidate components ⚠️](5-components.md) | 1 KB | no |
| 6 | [Brand cohesion](6-brand-cohesion.md) | 1 KB | no |
| 7 | [Replace the emoji icon system](7-icons.md) | 1 KB | no |
| 8 | [Missing states and quality](8-states-quality.md) | 1 KB | no |
| 9 | [Add the missing marketplace screens ⚠️](9-screens.md) | 5 KB | yes — 16 undesigned live routes |

After the last prompt: **re-publish**, then send me the published URL. I will re-run the audit and the parity harness against the new build before Phase C.
