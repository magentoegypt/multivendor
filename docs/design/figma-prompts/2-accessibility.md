# Prompt 2 — Accessibility P0 ⚠️

Paste the block below into the Figma Make chat. Wait for the build to settle before the next prompt.

---


> Fix these, in this order:
>
> 1. **Give every control an accessible name.** The bundle contains `aria-label` 0 times, `sr-only`
>    once, `htmlFor` 0 times. Add `aria-label` to all 19+ icon-only buttons (wishlist, cart, quantity
>    steppers, carousel arrows, slide dots, search submit, close buttons).
> 2. **Associate every form control with its label.** 28 `<label>` elements exist with no `htmlFor`, and
>    no input has an `id`. Add matching `id`/`htmlFor` pairs throughout, including checkout.
> 3. **Make focus visible.** 36 `:focus-visible` rules exist but paint `--ring: #0003` at ~1.6:1.
>    Switch to `--focus-ring` (#c2410c, 5.18:1) on light surfaces and `--focus-ring-inverse` (white) on
>    navy, at 2px with a 2px offset. Restore outlines on the site search, mobile search and header
>    category select, which currently remove theirs with no replacement.
> 4. **Remove hover-only affordances.** Add-to-cart and wishlist on the product grid are `opacity-0`
>    until hover, so they do not exist on touch. Make them always visible, or reveal on
>    `:focus-within` as well as hover.
> 5. **Add live regions.** `aria-live` is used 0 times, so add-to-cart, filter changes and search
>    results are silent. Add a polite live region for cart and result-count changes.
> 6. **Fix the dark "Picked For You" band** — its text runs 1.54:1 to 3.76:1. Every layer must clear
>    4.5:1 (use `--on-inverse` / `--on-inverse-muted`).
> 7. **Fix footer contrast** — legal links and copyright sit at ~2.2:1 on navy, the lowest in the build.
> 8. **Raise sub-14px type.** 188 of 475 sized declarations are below 14px, some as low as 7px. Set a
>    12px floor for decorative text and 14px for anything carrying commerce data.
> 9. **Guard motion.** `prefers-reduced-motion` appears 0 times while three infinite animations and 12
>    hover-scale transforms run unconditionally.
> 10. **Fix touch targets.** At least 14 interactive controls compute under 44px; four are under the
>     WCAG 2.2 floor of 24px. Carousel slide dots are 6×6px.
> 11. **Give the rating a text alternative.** Stars are 1.35:1 filled-vs-empty. Always render the
>     numeric value beside them, and use `--rating-star` (#d97706).
> 12. **Stop nesting `<button>` inside `<a>`** on the product card — it is invalid HTML and breaks
>     keyboard and screen-reader behaviour.
> 13. **One `<h1>` per page, stable.** The homepage `<h1>` is currently the carousel headline and
>     changes every 4.5 seconds; the Search page has no `<h1>` at all.
