<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Api;

/**
 * Runs code as the storefront of one store view: frontend area, that store's
 * theme, locale and translations.
 *
 * WHY. The graphql area has no design theme, so theme translations
 * (app/design/.../hub-market/i18n/*.csv) do not load there. The website's
 * English brand names ("ابل" -> "Apple"), localised seller names and every
 * section title the theme translates come ONLY from those files through __().
 * Anything that builds a label the website shows must therefore run inside
 * run() or it will differ from the website.
 *
 * Re-entrant: nested run() calls for the same store reuse the outer emulation
 * (Magento's Emulation allows one level only, and a nested stop would end the
 * outer one early). A nested call for a DIFFERENT store is refused with a
 * logged warning and runs the callback un-emulated rather than corrupting the
 * outer environment.
 */
interface StorefrontEmulationInterface
{
    /**
     * Invoke $callback inside frontend emulation of $storeId and return its result.
     *
     * The emulation is always stopped, also when the callback throws.
     *
     * @template T
     * @param int $storeId
     * @param callable(): T $callback
     * @return T
     */
    public function run(int $storeId, callable $callback): mixed;

    /**
     * True while a run() is in progress (any store).
     */
    public function isActive(): bool;
}
