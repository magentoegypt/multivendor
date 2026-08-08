# Prompt 7 — Replace the emoji icon system

Paste the block below into the Figma Make chat. Wait for the build to settle before the next prompt.

---


> **51 distinct emoji code points** are doing icon duty — in the category nav, section headings, footer
> links, bundle filter chips, and as functional glyphs (`✓`, `★`, `♡`). Emoji render differently per
> OS, cannot be recoloured or optically aligned, are announced literally by screen readers ("heavy
> black heart"), and several are direction-bearing or contain Latin text and so cannot be localised.
>
> Replace all of them with the lucide icon set already in the bundle (47 lucide icons are present, so
> two parallel icon systems currently express the same concepts), on a 24px grid at one stroke weight.
> Where an emoji is decorative, mark it `aria-hidden`; where it is functional, give it a named icon and
> an accessible label.
>
> **Emit them as a single inline SVG sprite** referenced with `<use href="#icon-name">`, not as
> individual files and not as an icon font. That is what the Magento theme will consume, so matching it
> here keeps design and code on the same system. It also means icons inherit `currentColor` (so they
> recolour from the token layer), mirror correctly in RTL, and cost no extra request.
>
> Name each symbol semantically (`icon-cart`, `icon-vendor-verified`, `icon-delivery`), not visually
> (`icon-truck`), so the sprite survives a redesign.
