# Prompt 6 — Brand cohesion

Paste the block below into the Figma Make chat. Wait for the build to settle before the next prompt.

---


> 1. **Remove the third and fourth palettes.** 93 `blue-*` utilities and 12 `indigo/violet/purple`
>    utilities are in use — most visibly the "Picked For You" AI band, which is a self-contained
>    indigo/violet mini-palette. Restate it in `--primary` + `--accent`.
> 2. **Collapse the five near-duplicate oranges** (including `#c85c2c`, used 95 times and never
>    declared) into `--accent` / `--accent-hover` / `--accent-strong`.
> 3. **Collapse the eight undeclared navy/near-black shades** into `--primary` and `--neutral-900`.
> 4. **Replace the 8 Material pastel category tiles and 4 pale section bands** — 16 colours from a
>    foreign design system — with `--surface-muted`.
> 5. **Retire the warm neutral ramp** (`#f6f4f1`, `#ede9e3`, `#c9c4bc`, `#9e9890`) in favour of the cool
>    one. `#9e9890` is 2.86:1 on white and fails even the non-text threshold.
> 6. **Unify the three visual systems.** Home is dense-commercial, Vendors is editorial-luxury, and the
>    checkout funnel is unbranded; the Platform page runs two at once. Converge on the commercial
>    system and keep at most one editorial moment (the seller-directory hero).
> 7. **Delete the `.dark` block** — it is pure shadcn greyscale with no brand in it, and the shell
>    advertises `color-scheme: light dark` while no `prefers-color-scheme` rules exist.
