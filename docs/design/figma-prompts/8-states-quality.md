# Prompt 8 — Missing states and quality

Paste the block below into the Figma Make chat. Wait for the build to settle before the next prompt.

---


> Add the states that do not exist anywhere in the bundle: **loading / skeleton, empty, error,
> out-of-stock, disabled, no-results**. Then:
> - lazy-load the 132 remote images and give them intrinsic dimensions and responsive sources
> - unpin `html{font-size:16px}` so the user's browser font-size preference is respected
> - make type responsive above 36px — only 6 of 475 size declarations are responsive, and 48px/128px
>   headings are unguarded on mobile
> - raise form controls to 16px so iOS does not zoom on focus
> - add a skip link, make the category bar a `<nav>`, and give the breadcrumb real markup instead of
>   `div`s with literal `/` spans
> - fix the Search page: its sticky search bar is `z-10` under a `z-50` sticky header at the same offset
> - make header search actually pass its query — it is currently discarded, and the search page ignores
>   the URL
> - fix the broken links: the Electronics category tile targets a non-existent slug, and **6 of 10
>   vendor cards** link to "Vendor not found"
> - give the hero carousel a pause control
> - **verify mobile** — I could not check it, because the published site renders at a fixed desktop
>   width in an iframe. Check every page in Make's own device preview.
