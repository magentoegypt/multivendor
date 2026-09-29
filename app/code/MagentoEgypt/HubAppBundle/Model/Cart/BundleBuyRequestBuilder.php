<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Cart;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * hmAddBundleToCart input -> the buy request the website's bundle form posts. Pure: unit-tested.
 *
 * The app sends the uids GraphQL gave it:
 *   selection_uid              BundleItemOption.uid = base64("bundle/<option id>/<selection id>/<default qty>")
 *   configurable_option_uids   ConfigurableProductOptionsValues.uid = base64("configurable/<attribute id>/<value>")
 *
 * and gets back exactly what the new_bundle product page posts
 * (BundleExtend bundle-package.js, SetSuperConfigurableProduct):
 *   product, qty
 *   bundle_option[<option id>]      = selection id, or a list for several selections of one option
 *   bundle_option_qty[<option id>]  = qty, or [<selection id> => qty]; only when the app sent a quantity
 *                                     (Magento applies it only to selections whose qty the customer may change)
 *   super_attribute[<child product id>][<attribute id>] = value, one map per configurable selection
 *
 * Everything the app sends is checked against the bundle's own selections
 * ($bundleSelections: selection id => option id, child product id, child type);
 * a choice that does not belong to this bundle is refused, never guessed.
 */
class BundleBuyRequestBuilder
{
    private const BUNDLE_UID = 'bundle';
    private const CONFIGURABLE_UID = 'configurable';
    private const CONFIGURABLE_TYPE = 'configurable';

    /**
     * @param int $bundleProductId
     * @param float $quantity number of bundles, > 0
     * @param array<int, array<string, mixed>> $selections HmBundleSelectionInput values
     * @param array<int, array{option_id: int, product_id: int, type_id: string}> $bundleSelections
     *        every selection of the bundle, by selection id
     * @return array<string, mixed> buy request data
     * @throws LocalizedException with a message the app can show
     */
    public function build(int $bundleProductId, float $quantity, array $selections, array $bundleSelections): array
    {
        if ($quantity <= 0) {
            throw new LocalizedException(new Phrase('The product quantity should be greater than 0'));
        }
        if (!$selections) {
            throw new LocalizedException(new Phrase('Please specify product option(s).'));
        }

        $byOption = [];          // option id => [selection id => qty|null]
        $superAttribute = [];    // child product id => [attribute id => value]
        $seen = [];

        foreach (array_values($selections) as $selection) {
            $selectionUid = (string) ($selection['selection_uid'] ?? '');
            $parts = self::decodeUid($selectionUid);
            if ($parts === null
                || count($parts) !== 4
                || $parts[0] !== self::BUNDLE_UID
                || !self::isId($parts[1])
                || !self::isId($parts[2])
            ) {
                throw new LocalizedException(
                    new Phrase('The bundle option "%1" is not valid.', [$selectionUid])
                );
            }
            $optionId = (int) $parts[1];
            $selectionId = (int) $parts[2];

            $known = $bundleSelections[$selectionId] ?? null;
            if ($known === null || (int) $known['option_id'] !== $optionId) {
                throw new LocalizedException(new Phrase('The options you selected are not available.'));
            }
            if (isset($seen[$selectionId])) {
                throw new LocalizedException(
                    new Phrase('The bundle option "%1" was sent more than once.', [$selectionUid])
                );
            }
            $seen[$selectionId] = true;

            $qty = null;
            if (isset($selection['quantity'])) {
                $qty = (float) $selection['quantity'];
                if ($qty <= 0) {
                    throw new LocalizedException(new Phrase('The product quantity should be greater than 0'));
                }
            }
            $byOption[$optionId][$selectionId] = $qty;

            $choices = self::configurableChoices($selection['configurable_option_uids'] ?? null);
            $isConfigurable = (string) $known['type_id'] === self::CONFIGURABLE_TYPE;
            if ($choices && !$isConfigurable) {
                throw new LocalizedException(
                    new Phrase('The bundle option "%1" has no options to choose.', [$selectionUid])
                );
            }
            if ($isConfigurable) {
                if (!$choices) {
                    throw new LocalizedException(
                        new Phrase('Choose the options of every configurable product in the bundle.')
                    );
                }
                $childId = (int) $known['product_id'];
                if (isset($superAttribute[$childId]) && $superAttribute[$childId] !== $choices) {
                    throw new LocalizedException(
                        new Phrase('The same product was chosen twice with different options.')
                    );
                }
                $superAttribute[$childId] = $choices;
            }
        }

        $request = [
            'product' => $bundleProductId,
            'qty' => $quantity,
            'bundle_option' => [],
        ];
        foreach ($byOption as $optionId => $chosen) {
            $selectionIds = array_keys($chosen);
            $request['bundle_option'][$optionId] = count($selectionIds) === 1 ? $selectionIds[0] : $selectionIds;

            $quantities = array_filter($chosen, static fn (?float $qty): bool => $qty !== null);
            if ($quantities) {
                $request['bundle_option_qty'][$optionId] = count($selectionIds) === 1
                    ? reset($quantities)
                    : $quantities;
            }
        }
        if ($superAttribute) {
            $request['super_attribute'] = $superAttribute;
        }

        return $request;
    }

    /**
     * attribute id => value for one selection's configurable uids.
     *
     * @return array<int, int>
     * @throws LocalizedException
     */
    private static function configurableChoices(mixed $uids): array
    {
        if ($uids === null || $uids === []) {
            return [];
        }
        if (!is_array($uids)) {
            throw new LocalizedException(new Phrase('The configurable options are not valid.'));
        }

        $choices = [];
        foreach ($uids as $uid) {
            $parts = self::decodeUid((string) $uid);
            if ($parts === null
                || count($parts) !== 3
                || $parts[0] !== self::CONFIGURABLE_UID
                || !self::isId($parts[1])
                || !self::isId($parts[2])
            ) {
                throw new LocalizedException(
                    new Phrase('The configurable option "%1" is not valid.', [(string) $uid])
                );
            }
            $attributeId = (int) $parts[1];
            if (isset($choices[$attributeId]) && $choices[$attributeId] !== (int) $parts[2]) {
                throw new LocalizedException(new Phrase('Choose one value per configurable option.'));
            }
            $choices[$attributeId] = (int) $parts[2];
        }
        ksort($choices);

        return $choices;
    }

    /**
     * The parts of a GraphQL uid (strict base64, as Magento\Framework\GraphQl\Query\Uid), or null.
     *
     * @return string[]|null
     */
    private static function decodeUid(string $uid): ?array
    {
        if ($uid === '') {
            return null;
        }
        $decoded = base64_decode($uid, true);
        if ($decoded === false || base64_encode($decoded) !== $uid) {
            return null;
        }

        return explode('/', $decoded);
    }

    private static function isId(string $value): bool
    {
        return (bool) preg_match('/^[1-9]\d{0,9}$/', $value);
    }
}
