# Prompt 3 — Arabic and RTL P0 ⚠️

Paste the block below into the Figma Make chat. Wait for the build to settle before the next prompt.

---


> This design ships to a live Arabic store view. Today the language toggle only flips `dir` and `lang` —
> there is no translation layer, and 100% of copy stays English.
>
> 1. **Add an Arabic typeface.** Neither loaded font contains a single Arabic glyph — DM Sans ships
>    `latin`/`latin-ext` only, Playfair Display ships `latin`/`latin-ext`/`cyrillic`/`vietnamese`. Load
>    **IBM Plex Sans Arabic** (it carries both `arabic` and `latin` subsets, so numerals and Latin
>    fragments inside Arabic text stay in one family) and apply it via the `:lang(ar), [dir="rtl"]`
>    block in the new token sheet.
> 2. **Remove the 45 inline `fontFamily` style objects** — they make the Arabic swap impossible from a
>    stylesheet.
> 3. **Stop the logo mirroring.** It is two flex siblings, so RTL reverses "MECommerce" to
>    "**Commerce**ME". Lock it with `dir="ltr"` and treat it as a single indivisible wordmark.
> 4. **Add bidi isolation.** There is no `<bdi>`, no `dir` attribute and no isolation mark anywhere. Wrap
>    every LTR-locked value that can appear inside Arabic text — prices, SKUs, order IDs, phone numbers,
>    URLs, Latin brand names — in `<bdi>`. This is what currently produces `...Search products` with a
>    leading ellipsis and `.fashion — one marketplace` with a leading full stop.
> 5. **Mirror directional things.** ~45 physical utilities remain: `left-N` (15), `right-N` (16),
>    `ml-auto` (5), `border-r` (6), `border-l` (1), `text-left` (2). Convert to logical equivalents
>    (`start`/`end`, `ms-auto`, `border-e`, `text-start`). Specifically:
>    - carousel prev/next are pinned `left-3`/`right-3` with non-mirroring chevrons
>    - product-card badges are pinned physically, so the discount badge and wishlist heart never swap
>    - the quantity stepper uses `border-r` on minus and `border-l` on plus, so in RTL both dividers
>      move outward and the middle cell loses its separators
>    - search icons and select carets are absolutely positioned physically
>    - the hero scrim is `bg-gradient-to-r`, so in RTL the right-aligned white text lands on the bright
>      side of the photograph
> 6. **Fix the discount badge.** It is built as `["-", pct, "%"]`, so in RTL the minus detaches and
>    `-25%` renders as `25%-`. Compose it as a single pre-formatted string.
> 7. **Replace `←`/`→` literals** in pagination and the bundle back-link — Unicode does not bidi-mirror
>    those characters. Use mirroring icon components.
> 8. **Drop letter-spacing and uppercase in Arabic.** 52 `tracking-*` utilities and 42 `uppercase`
>    transforms are destructive (letter-spacing breaks cursive joining) or meaningless in Arabic.
> 9. **Raise Arabic line-height.** Heading line-heights of 1.0–1.25 plus fixed min-heights on clamped
>    titles will clip Arabic ascenders, descenders and diacritics. Use the `--leading-arabic-*` tokens.
> 10. **Add real Arabic copy** to Home, Category, Product and Checkout so Arabic typography can actually
>     be judged. Two Arabic strings currently render inside a `lang="en"` document with no `lang` of
>     their own.
> 11. **Persist the language choice** — it is component state only, so it resets to English LTR on every
>     reload and cannot be linked to.
