# Prompt 5 — Consolidate components ⚠️

Paste the block below into the Figma Make chat. Wait for the build to settle before the next prompt.

---


> The build has no shared component layer for its most repeated elements. Collapse each of these into
> one component with declared variants:
>
> | Element | Distinct renderings today |
> |---|---|
> | Product card | **12** (only one is a component; one variant is dead code) |
> | Vendor / seller card | **9** |
> | Star rating | **11** inline loops, 4 sizes, 3 colours, 2 always render 5/5 |
> | Price treatment | **11** current-price + **8** was-price |
> | Badge | ~**20** ad-hoc treatments |
> | Button | **74** raw `<button>`s — 7 primary fills, 3 hover oranges, 10 icon sizes |
> | Section header | ~**12** configurations, 7 different "see all" labels |
>
> Also: use the shadcn Tabs, Select, Pagination, Breadcrumb, Progress and Carousel already compiled into
> the bundle instead of the hand-rolled versions, and delete the unused shadcn/Radix surface — 634 of
> 1,356 compiled selectors are dead, and the Magento build must not carry them.
