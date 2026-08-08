# Prompt 1 — Replace the token layer ⚠️

Paste the block below into the Figma Make chat. Wait for the build to settle before the next prompt.

Context for this whole series (state once, at the start of the session):

> This design will be implemented as a Magento 2.4.8 theme for a Gulf multi-vendor marketplace
> (Vnecoms) serving UAE, KSA, Qatar, Bahrain and Kuwait, with live English (LTR) and Arabic (RTL)
> store views. Checkout runs Amasty One Step Checkout Pro. Apply changes to the existing app; do not
> start over.

---


> Replace the entire contents of `src/styles/theme.css` with the stylesheet below, then remove every
> `oklch()` colour block that remains — those are shadcn dark-mode defaults that were never part of this
> brand, and they are what created the second, competing neutral ramp.
>
> Then refactor components so colour comes **only** from these tokens. Today the token layer is
> effectively dead code: of roughly 390 colour utilities in the app, almost none resolve to a declared
> token. Replace every hard-coded hex and every raw Tailwind palette utility (`blue-600`, `gray-400`,
> `red-500`, `green-600`, `amber-400`, `indigo-*`, `violet-*`, `purple-*`) with the semantic token that
> matches its role.
>
```css
/* ============================================================================
   MECommerce — corrected design tokens
   ----------------------------------------------------------------------------
   Drop-in replacement for the Figma Make file's  src/styles/theme.css

   Every colour pairing in this file has been contrast-checked with a real WCAG
   2.x relative-luminance calculation. Ratios are quoted inline. Nothing here is
   an estimate.

   What changed vs. the generated theme
   ------------------------------------
   1. The accent is split. #f26522 on white is only 3.15:1, so it could never
      legally carry text. It is now a FILL colour, with a separate text-safe
      orange and an explicit on-accent label colour.
   2. One neutral ramp. The generated theme ran a cool ramp and a warm ramp
      (the untouched shadcn `sidebar` defaults) side by side. The warm set is
      retired — see "RETIRED" at the bottom.
   3. Commerce semantics exist. Price, was-price, discount, rating, stock and
      vendor-verification were previously ad-hoc literals.
   4. Arabic is a first-class script, not a fallback. Neither DM Sans nor
      Playfair Display contains a single Arabic glyph (verified against the
      Google Fonts unicode-range: both ship latin/latin-ext only).
   5. Scales are closed. No loose px values for radius, spacing or shadow.
   ========================================================================== */

:root {
  /* ==========================================================================
     1. BRAND
     ====================================================================== */
  --brand-navy:            #0f2144;  /* primary. white on it = 15.89:1        */
  --brand-navy-hover:      #16305f;
  --brand-orange:          #f26522;  /* accent FILL only — see §2             */

  --primary:               var(--brand-navy);
  --primary-hover:         var(--brand-navy-hover);
  --on-primary:            #ffffff;  /* 15.89:1 — AAA                         */

  /* ==========================================================================
     2. ACCENT SYSTEM  — the single most important correction
     --------------------------------------------------------------------------
     #f26522 on white is 3.15:1. That is fine for a fill or an icon (WCAG 1.4.11
     needs 3:1 for UI) but FAILS the 4.5:1 required of normal-size text — which
     is exactly how the generated design used it: prices, vendor names and
     button labels.

     Resolution:
       --accent          fills, icons, borders, decorative bars
       --on-accent       the label colour on any accent fill (navy, NOT white:
                         white on #f26522 is 3.15:1 and fails; navy is 5.04:1)
       --accent-strong   orange TEXT on light surfaces — prices, links
     ====================================================================== */
  --accent:                #f26522;  /* fill.        vs white  3.15:1 (UI ok) */
  --accent-hover:          #e85d18;  /* fill hover.  navy on it 4.55:1        */
  --accent-strong:         #c2410c;  /* TEXT.        vs white  5.18:1         */
  --accent-strong-hover:   #9a3412;  /*              vs white  7.31:1         */
  --on-accent:             #0f2144;  /* navy on #f26522        5.04:1         */
  --accent-subtle:         #fff4ef;  /* tint background                       */

  /* NOTE: do not darken --accent-hover past #e85d18. Below that the navy
     --on-accent label drops under 4.5:1 (navy on #d64f10 is 3.77:1).          */

  /* ==========================================================================
     3. NEUTRAL RAMP  (cool — the only ramp)
     ====================================================================== */
  --neutral-0:             #ffffff;
  --neutral-50:            #fafbfd;
  --neutral-100:           #f5f7fa;  /* muted surfaces                        */
  --neutral-200:           #e8ecf3;  /* dividers, card borders (decorative)   */
  --neutral-300:           #cbd3e2;  /* empty rating star, track              */
  --neutral-400:           #9aa5bb;  /* disabled text only (exempt from AA)   */
  --neutral-500:           #6b7280;  /* secondary text.  vs white 4.83:1      */
  --neutral-600:           #535d70;  /* placeholder.     vs white 6.63:1      */
  --neutral-700:           #3d4759;  /* strong secondary vs white 9.36:1      */
  --neutral-900:           #1a1a2e;  /* body text.       vs white 17.06:1     */

  /* ==========================================================================
     4. SEMANTIC SURFACES / TEXT / BORDERS
     ====================================================================== */
  --background:            var(--neutral-0);
  --surface:               var(--neutral-0);
  --surface-muted:         var(--neutral-100);
  --surface-inverse:       var(--brand-navy);
  --surface-input:         var(--neutral-0);   /* was warm #f6f4f1 — retired  */

  --foreground:            var(--neutral-900);
  --foreground-muted:      var(--neutral-500);
  --foreground-placeholder:var(--neutral-600); /* #6b7280 on a tinted input
                                                  was 4.4:1 — just failing    */
  --foreground-disabled:   var(--neutral-400);
  --on-inverse:            var(--neutral-0);
  --on-inverse-muted:      var(--neutral-300); /* on navy 10.56:1             */

  /* Two border roles. Decorative dividers are exempt from 1.4.11; the boundary
     of an actual control is not.                                             */
  --border-subtle:         var(--neutral-200); /* dividers, card edges        */
  --border-strong:         #7d879c;            /* inputs, selects  3.61:1     */

  --icon:                  var(--neutral-500); /* 4.83:1                      */
  --icon-muted:            #7d879c;            /* 3.61:1 — the floor          */

  /* ==========================================================================
     5. FOCUS
     ====================================================================== */
  --focus-ring:            var(--accent-strong);      /* on light  5.18:1     */
  --focus-ring-inverse:    var(--neutral-0);          /* on navy  15.89:1     */
  --focus-ring-width:      2px;
  --focus-ring-offset:     2px;

  /* ==========================================================================
     6. COMMERCE SEMANTICS  (absent from the generated theme)
     ====================================================================== */
  --price:                 var(--accent-strong);  /* 5.18:1 — was 3.15:1      */
  --price-was:             var(--neutral-500);    /* struck-through           */
  --price-inverse:         var(--accent);         /* on navy 5.04:1           */

  --discount-bg:           #b3261e;               /* white on it 6.54:1       */
  --discount-fg:           #ffffff;

  --rating-star:           #d97706;               /* vs white 3.19:1          */
  --rating-star-empty:     var(--neutral-300);
  /* Always render the numeric rating too — colour must not be the sole
     carrier of the value (WCAG 1.4.1).                                       */

  --stock-in:              #0f7b3f;               /* vs white 5.35:1          */
  --stock-low:             #b45309;               /* vs white 5.02:1          */
  --stock-out:             var(--neutral-500);

  --vendor-verified:       #1d4ed8;               /* vs white 6.70:1          */
  --bundle-badge-bg:       var(--brand-navy);
  --bundle-badge-fg:       #ffffff;
  --free-delivery:         var(--stock-in);

  /* ==========================================================================
     7. FEEDBACK
     ====================================================================== */
  --success:               #0f7b3f;   /* white on it 5.35:1                   */
  --warning:               #b45309;   /* white on it 5.02:1                   */
  --danger:                #c0392b;   /* white on it 5.44:1                   */
  --info:                  #1d4ed8;   /* white on it 6.70:1                   */
  --on-status:             #ffffff;

  --success-subtle:        #eaf6ef;
  --warning-subtle:        #fdf3e7;
  --danger-subtle:         #fbecea;
  --info-subtle:           #eaf0fd;

  /* ==========================================================================
     8. TYPOGRAPHY
     --------------------------------------------------------------------------
     Latin: DM Sans (UI/body) + Playfair Display (display only).
     Arabic: IBM Plex Sans Arabic — chosen because it ships BOTH `arabic` and
     `latin` subsets, so Latin fragments and numerals embedded in Arabic text
     stay in one family instead of falling back mid-sentence.

     Playfair Display must NOT be used for product titles or prices. A display
     serif at 14–16px measurably slows scanning on a listing page, and it has
     no Arabic counterpart, so the two locales would diverge structurally.
     ====================================================================== */
  --font-sans:    "DM Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
  --font-display: "Playfair Display", Georgia, "Times New Roman", serif;
  --font-arabic:  "IBM Plex Sans Arabic", "Tajawal", "Noto Sans Arabic", sans-serif;
  --font-mono:    ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;

  /* Type scale — closed. */
  --text-xs:    0.75rem;   --leading-xs:   1.5;
  --text-sm:    0.875rem;  --leading-sm:   1.5;
  --text-base:  1rem;      --leading-base: 1.5;
  --text-lg:    1.125rem;  --leading-lg:   1.45;
  --text-xl:    1.25rem;   --leading-xl:   1.4;
  --text-2xl:   1.5rem;    --leading-2xl:  1.35;
  --text-3xl:   1.875rem;  --leading-3xl:  1.25;
  --text-4xl:   2.25rem;   --leading-4xl:  1.2;
  --text-5xl:   3rem;      --leading-5xl:  1.1;

  /* Arabic needs more leading than Latin at the same size — ascenders and
     descenders are deeper and diacritics sit above the line.                  */
  --leading-arabic-body:    1.8;
  --leading-arabic-heading: 1.5;
  --arabic-size-adjust:     1.05;  /* Arabic reads small next to DM Sans      */

  --weight-regular: 400;
  --weight-medium:  500;
  --weight-semibold:600;
  --weight-bold:    700;
  --weight-extra:   800;
  /* DM Sans 300 (Light) is deliberately absent — it is unusable below 18px.  */

  --tracking-tight:  -0.025em;
  --tracking-normal:  0;
  --tracking-wide:    0.025em;
  --tracking-wider:   0.05em;
  /* Never apply letter-spacing to Arabic — it breaks cursive joining.        */

  /* ==========================================================================
     9. SPACING / RADIUS / SHADOW / MOTION / LAYERS
     ====================================================================== */
  --space-1: 0.25rem;  --space-2: 0.5rem;   --space-3: 0.75rem;
  --space-4: 1rem;     --space-5: 1.25rem;  --space-6: 1.5rem;
  --space-8: 2rem;     --space-10:2.5rem;   --space-12:3rem;
  --space-16:4rem;     --space-20:5rem;     --space-24:6rem;

  --radius-xs:   0.125rem;
  --radius-sm:   0.375rem;
  --radius:      0.75rem;   /* the default                                    */
  --radius-lg:   1rem;
  --radius-full: 9999px;

  --shadow-sm: 0 1px 2px rgba(15, 33, 68, 0.06);
  --shadow:    0 4px 12px rgba(15, 33, 68, 0.08);
  --shadow-lg: 0 12px 32px rgba(15, 33, 68, 0.12);
  --shadow-accent: 0 6px 20px rgba(242, 101, 34, 0.20);

  --duration-fast: 120ms;
  --duration:      200ms;
  --duration-slow: 320ms;
  --ease: cubic-bezier(0.4, 0, 0.2, 1);

  --z-base: 0; --z-sticky: 100; --z-drawer: 200;
  --z-modal: 300; --z-toast: 400;

  --container: 1440px;
  --container-pad: var(--space-8);
  --bp-sm: 640px; --bp-md: 768px; --bp-lg: 1024px; --bp-xl: 1280px; --bp-2xl: 1440px;
}

/* ============================================================================
   ARABIC / RTL
   ========================================================================== */
:root:lang(ar), [dir="rtl"] {
  --font-sans:    var(--font-arabic);
  --font-display: var(--font-arabic);   /* no Arabic serif pairs with Playfair;
                                           use weight for hierarchy instead   */
  --leading-base: var(--leading-arabic-body);
  --leading-lg:   var(--leading-arabic-body);
  --tracking-tight: 0;
  --tracking-wide:  0;
  --tracking-wider: 0;
}

@media (prefers-reduced-motion: reduce) {
  :root { --duration-fast: 0ms; --duration: 0ms; --duration-slow: 0ms; }
}

/* ============================================================================
   RETIRED — remove these literals from the codebase
   ----------------------------------------------------------------------------
   #c85c2c   ×10  undeclared accent hover        -> --accent-hover (#e85d18)
   #0a0a0a   ×7   second near-black              -> --neutral-900
   #6366f199 ×2   indigo, entirely off-brand     -> --info or --primary
   #9e9890        warm grey, 2.86:1 on white     -> --neutral-500 (fails AA)
   #c9c4bc        warm switch track              -> --neutral-300
   #ccc           generic grey                   -> --neutral-300
   #f6f4f1        warm input background          -> --surface-input (#fff)
   #ede9e3        warm sidebar accent            -> --neutral-100
   green hero CTA                                -> --accent + --on-accent
   purple/indigo "AI Engine" section             -> --primary + --accent
   8 pastel category tiles                       -> --surface-muted, one tint
   pale-green / pale-pink section bands          -> --surface-muted
   #f59e0b star fill (2.15:1, fails 1.4.11)      -> --rating-star (#d97706)

   Also delete: every `oklch()` block. Those are shadcn dark-mode defaults that
   were never part of this brand, and they are what produced the second neutral
   ramp in the first place.
   ========================================================================== */
```
>
> Key rules that must survive the refactor:
> - `--accent` (#f26522) is a **fill** colour. It must never carry text on white — it is 3.15:1.
> - Text labels on an accent fill use `--on-accent` (navy #0f2144, 5.04:1). **Not white** — white on
>   #f26522 is 3.15:1 and fails.
> - Orange **text** on light surfaces uses `--accent-strong` (#c2410c, 5.18:1). Prices use this.
> - Do not darken `--accent-hover` past #e85d18; below that the navy label drops under 4.5:1.
