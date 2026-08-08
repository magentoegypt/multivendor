# Prompt 4 — Currency and locale P0

Paste the block below into the Figma Make chat. Wait for the build to settle before the next prompt.

---


> 1. **Every price is `$`.** Replace with **AED** as the default, formatted via `Intl.NumberFormat`
>    (used 0 times today) with the store locale and currency, not string concatenation.
> 2. **`toFixed(2)` is wrong for this region.** It is used 16 times. Kuwaiti dinar and Bahraini dinar
>    use **three** decimal places. Let `Intl.NumberFormat` decide precision per currency.
> 3. **Remove the hard-coded `Tax (10%)` line** in the cart. None of the five target countries uses 10%
>    (UAE/KSA/Bahrain/Oman VAT differ; Kuwait and Qatar have none). Label it VAT and drive it from data.
> 4. **Reconcile the free-shipping threshold** — three different values in two currencies across four
>    components, against a header promising "AED 150".
> 5. **Pass a locale to `toLocaleDateString()` / `toLocaleString()`** — they are called bare, so dates
>    and thousands separators follow the visitor's browser rather than the store view.
> 6. **Add `font-variant-numeric: tabular-nums`** to money columns in cart and checkout so decimals
>    align.
> 7. **Replace the US address form in checkout** — it has `+1 (555)` phone, State/ZIP, New York/NY/10001
>    placeholders and **no Country field at all**. Use a Gulf address model (country, emirate/city,
>    area/district, street, building, landmark) and Gulf payment methods (Mada, Tap, Tabby, STC Pay,
>    cash on delivery) to match the footer's own claims.
