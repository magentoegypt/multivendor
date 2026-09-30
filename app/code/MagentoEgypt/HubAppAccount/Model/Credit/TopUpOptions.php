<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Credit;

/**
 * Buying store credit the way the website sells it, from its store_credit products (Vnecoms Credit):
 * the amounts on offer (HmStoreCreditAccount.top_up) and, for an amount, the product and the buy request
 * its product page posts (hmAddCreditToCart). Pure: TopUpCatalog loads the products. Unit-tested.
 *
 * The website lists every store credit product on its Buy Credit page (vstorecredit/buy, the "Buy Credit"
 * button of My Credit). Each product sells credit one of three ways (Vnecoms\Credit\Model\Source\Type):
 *   - FIXED (1): credit_value_fixed for credit_price. The product page has no field; the buy request is
 *     the product and the quantity, and Product\Type\Credit::_prepareProduct() reads both from the product;
 *   - DROPDOWN (2): credit_value_dropdown, pairs of credit_value and credit_price. The page posts
 *     store_credit[credit_value] = one of the values; _prepareProduct() looks its price up;
 *   - CUSTOM (3): credit_value_custom from..to and credit_rate. The page posts
 *     store_credit[credit_value] = a whole number that its slider and field (Vnecoms_Credit/js/credit.js)
 *     keep within from..to, both cast to whole numbers by Block\Product\View\Type\Credit; the price is
 *     credit_value / credit_rate, rounded to cents. _prepareProduct() itself checks neither bound, so this
 *     class does: the app gets what the page allows, not what a hand-made request could get.
 *
 * The app shows one card: the fixed and dropdown amounts as presets (one per amount, smallest first, the
 * cheaper product when two sell the same amount) and a custom amount when a CUSTOM product exists (the
 * first one, by product id). An amount is bought with its preset's product when there is one, else with
 * the custom-amount product.
 *
 * Refused as the website would fail them, or never meant to sell: a credit or a price that is not above
 * 0 (a missing credit_price would make the product page sell credit for nothing), a CUSTOM product without
 * a usable range or with credit_rate 0 (_prepareProduct() would divide by it).
 */
final class TopUpOptions
{
    public const TYPE_FIXED = 1;
    public const TYPE_DROPDOWN = 2;
    public const TYPE_CUSTOM = 3;

    /** Amounts closer than this are the same amount (cents are the smallest unit). */
    private const EPSILON = 0.005;

    /**
     * @param list<array{product_id: int, sku: string, credit: float, price: float, option: ?string}> $presets
     * @param array{product_id: int, sku: string, min: int, max: int, rate: float}|null $custom
     */
    private function __construct(
        private readonly array $presets,
        private readonly ?array $custom
    ) {
    }

    /**
     * @param list<array<string, mixed>> $products store credit products on sale, each with id, sku,
     *     credit_type, credit_value_fixed, credit_price, credit_value_dropdown, credit_value_custom and
     *     credit_rate as the product holds them (arrays or their JSON)
     * @return self|null null when none of them sells anything
     */
    public static function fromProducts(array $products): ?self
    {
        $presets = [];
        $custom = null;
        usort($products, static fn (array $a, array $b): int => (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0));
        foreach ($products as $product) {
            $id = (int) ($product['id'] ?? 0);
            $sku = trim((string) ($product['sku'] ?? ''));
            if ($id <= 0 || $sku === '') {
                continue;
            }
            switch ((int) ($product['credit_type'] ?? 0)) {
                case self::TYPE_FIXED:
                    $credit = self::number($product['credit_value_fixed'] ?? null);
                    $price = self::number($product['credit_price'] ?? null);
                    if ($credit > 0 && $price > 0) {
                        $presets[] = ['product_id' => $id, 'sku' => $sku, 'credit' => $credit, 'price' => $price, 'option' => null];
                    }
                    break;
                case self::TYPE_DROPDOWN:
                    foreach (self::rows($product['credit_value_dropdown'] ?? null) as $row) {
                        $credit = self::number($row['credit_value'] ?? null);
                        $price = self::number($row['credit_price'] ?? null);
                        if ($credit > 0 && $price > 0) {
                            $presets[] = [
                                'product_id' => $id,
                                'sku' => $sku,
                                'credit' => $credit,
                                'price' => $price,
                                'option' => trim((string) $row['credit_value']),
                            ];
                        }
                    }
                    break;
                case self::TYPE_CUSTOM:
                    if ($custom !== null) {
                        break;
                    }
                    $range = self::range($product['credit_value_custom'] ?? null);
                    $rate = self::number($product['credit_rate'] ?? null);
                    if ($range !== null && $rate > 0) {
                        $custom = ['product_id' => $id, 'sku' => $sku, 'min' => $range[0], 'max' => $range[1], 'rate' => $rate];
                    }
                    break;
            }
        }

        $presets = self::onePerAmount($presets);
        if (!$presets && $custom === null) {
            return null;
        }

        return new self($presets, $custom);
    }

    /**
     * The product a custom amount buys; without one, the smallest preset's.
     */
    public function sku(): string
    {
        return $this->custom['sku'] ?? $this->presets[0]['sku'];
    }

    /**
     * Smallest custom amount; null when only presets can be bought.
     */
    public function min(): ?int
    {
        return $this->custom['min'] ?? null;
    }

    public function max(): ?int
    {
        return $this->custom['max'] ?? null;
    }

    /**
     * Credit per 1 of price for a custom amount (the price is amount / rate); null without one.
     */
    public function rate(): ?float
    {
        return $this->custom['rate'] ?? null;
    }

    /**
     * @return list<array{product_id: int, sku: string, credit: float, price: float, option: ?string}>
     *     smallest credit first
     */
    public function presets(): array
    {
        return $this->presets;
    }

    /**
     * The product and the buy request the website's product page posts for $amount of credit, or null
     * when $amount is neither a preset nor a whole number within the custom range.
     *
     * @return array{product_id: int, sku: string, request: array<string, mixed>}|null
     */
    public function buyRequest(float $amount): ?array
    {
        if (!is_finite($amount) || $amount <= 0) {
            return null;
        }
        foreach ($this->presets as $preset) {
            if (abs($preset['credit'] - $amount) < self::EPSILON) {
                $request = ['product' => $preset['product_id'], 'qty' => 1];
                if ($preset['option'] !== null) {
                    $request['store_credit'] = ['credit_value' => $preset['option']];
                }

                return ['product_id' => $preset['product_id'], 'sku' => $preset['sku'], 'request' => $request];
            }
        }
        if ($this->custom !== null) {
            $whole = round($amount);
            if (abs($whole - $amount) < self::EPSILON && $whole >= $this->custom['min'] && $whole <= $this->custom['max']) {
                return [
                    'product_id' => $this->custom['product_id'],
                    'sku' => $this->custom['sku'],
                    'request' => [
                        'product' => $this->custom['product_id'],
                        'qty' => 1,
                        'store_credit' => ['credit_value' => (int) $whole],
                    ],
                ];
            }
        }

        return null;
    }

    /**
     * The price of $amount of custom credit (credit_value / credit_rate, rounded to cents as
     * _prepareProduct() rounds it); null without a custom amount.
     */
    public function customPrice(float $amount): ?float
    {
        $rate = $this->rate();

        return $rate === null ? null : round($amount / $rate, 2);
    }

    /**
     * One preset per credit amount, the cheapest (then the oldest product) kept, smallest amount first.
     *
     * @param list<array{product_id: int, sku: string, credit: float, price: float, option: ?string}> $presets
     * @return list<array{product_id: int, sku: string, credit: float, price: float, option: ?string}>
     */
    private static function onePerAmount(array $presets): array
    {
        $byAmount = [];
        foreach ($presets as $preset) {
            $key = number_format($preset['credit'], 2, '.', '');
            if (!isset($byAmount[$key]) || $preset['price'] < $byAmount[$key]['price'] - self::EPSILON) {
                $byAmount[$key] = $preset;
            }
        }
        $out = array_values($byAmount);
        usort($out, static fn (array $a, array $b): int => $a['credit'] <=> $b['credit']);

        return $out;
    }

    /**
     * credit_value_custom as whole numbers from..to (1 at least), or null when it is not a usable range.
     *
     * @return array{int, int}|null
     */
    private static function range(mixed $value): ?array
    {
        $range = is_array($value) ? $value : self::decode($value);
        if (!isset($range['from'], $range['to']) || !is_numeric($range['from']) || !is_numeric($range['to'])) {
            return null;
        }
        $from = max(1, (int) $range['from']);
        $to = (int) $range['to'];

        return $to >= $from ? [$from, $to] : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function rows(mixed $value): array
    {
        $rows = is_array($value) ? $value : self::decode($value);

        return array_values(array_filter($rows, static fn ($row): bool => is_array($row) && isset($row['credit_value'])));
    }

    /**
     * @return array<mixed>
     */
    private static function decode(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function number(mixed $value): float
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (!is_numeric($value)) {
            return 0.0;
        }
        $number = (float) $value;

        return is_finite($number) ? round($number, 4) : 0.0;
    }
}
