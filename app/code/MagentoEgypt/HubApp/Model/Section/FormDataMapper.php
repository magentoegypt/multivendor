<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Section;

use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\HubApp\Model\Home\Section;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Source\ProductSort;
use MagentoEgypt\HubApp\Model\Source\SectionType;

/**
 * Admin form <-> table row, both directions, in one place.
 *
 * toRow() is the save WHITELIST (as HeroBanner's Save does): only the columns
 * below reach the model, whatever else the form posts. The option fields
 * (chip order / icons / tints, tile limit, featured only, badge) are folded into the
 * `options` JSON, keeping any key this form does not edit, and the JSON is
 * written with escaped unicode so an emoji glyph survives a utf8 (3-byte)
 * column.
 *
 * No Magento services: unit-testable as it is.
 */
class FormDataMapper
{
    public const MAX_TEXT = 255;
    public const MAX_URL = 512;
    public const MAX_CODES = 512;
    public const DEFAULT_TILE_LIMIT = 3;
    public const MAX_BADGE = 40;

    /**
     * Posted form values -> table row.
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $storedOptions the row's current options, decoded
     * @return array<string, mixed>
     * @throws LocalizedException on a value that cannot be saved as meant
     */
    public function toRow(array $post, array $storedOptions = []): array
    {
        $type = strtoupper(trim((string) ($post['type'] ?? '')));
        if (!SectionType::isKnown($type)) {
            throw new LocalizedException(__('Choose a section type.'));
        }

        $row = [
            'type' => $type,
            'title_en' => $this->text($post['title_en'] ?? null, self::MAX_TEXT),
            'title_ar' => $this->text($post['title_ar'] ?? null, self::MAX_TEXT),
            'subtitle_en' => $this->text($post['subtitle_en'] ?? null, self::MAX_TEXT),
            'subtitle_ar' => $this->text($post['subtitle_ar'] ?? null, self::MAX_TEXT),
            'category_id' => $this->positiveInt($post['category_id'] ?? null),
            'vendor_codes' => $this->codes($post['vendor_codes'] ?? null),
            'product_skus' => $this->skus($post['product_skus'] ?? null),
            'cms_identifier' => $this->text($post['cms_identifier'] ?? null, self::MAX_TEXT),
            'item_limit' => $this->limit($post['item_limit'] ?? null),
            'sort_by' => $this->sort($post['sort_by'] ?? null),
            'more_url' => $this->text($post['more_url'] ?? null, self::MAX_URL),
            'options' => $this->options($post, $storedOptions),
            'starts_at' => $this->utc($post['starts_at'] ?? null, 'starts_at'),
            'ends_at' => $this->utc($post['ends_at'] ?? null, 'ends_at'),
            'audience' => $this->audience($post['audience'] ?? null),
            'store_id' => max(0, (int) ($post['store_id'] ?? 0)),
            'position' => (int) ($post['position'] ?? 0),
            'is_active' => $this->flag($post['is_active'] ?? 1),
        ];

        if ($row['starts_at'] !== null && $row['ends_at'] !== null && $row['ends_at'] <= $row['starts_at']) {
            throw new LocalizedException(__('"Show until" must be later than "Show from".'));
        }
        if ($type === SectionType::CATEGORY_RAIL && $row['category_id'] === null) {
            throw new LocalizedException(__('A category rail needs a category.'));
        }
        if ($type === SectionType::PRODUCT_LIST && $row['product_skus'] === null) {
            throw new LocalizedException(__('A hand-picked list needs at least one SKU.'));
        }
        if ($type === SectionType::CMS_BLOCK && $row['cms_identifier'] === null) {
            throw new LocalizedException(__('Choose the CMS block to show.'));
        }

        return $row;
    }

    /**
     * Table row -> form values.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function toForm(array $row): array
    {
        $options = SectionContext::decodeOptions($row['options'] ?? null);

        $row['vendor_codes'] = SectionContext::splitList((string) ($row['vendor_codes'] ?? ''));
        $row['category_id'] = !empty($row['category_id']) ? (string) (int) $row['category_id'] : '';
        $row['chip_order'] = implode("\n", array_map('strval', (array) ($options['order'] ?? [])));
        $row['chip_icons'] = $this->pairsToText((array) ($options['icons'] ?? []));
        $row['chip_tints'] = $this->pairsToText((array) ($options['tints'] ?? []));
        $row['tile_limit'] = isset($options['tile_limit']) ? (string) (int) $options['tile_limit'] : '';
        $row['featured_only'] = !empty($options['featured_only']) ? '1' : '0';
        $row['badge_en'] = (string) ($options['badge_en'] ?? '');
        $row['badge_ar'] = (string) ($options['badge_ar'] ?? '');

        return $row;
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $stored
     */
    private function options(array $post, array $stored): ?string
    {
        $options = $stored;

        if (array_key_exists('chip_order', $post)) {
            $order = SectionContext::splitList((string) $post['chip_order']);
            $options['order'] = $order;
            if (!$order) {
                unset($options['order']);
            }
        }
        if (array_key_exists('chip_icons', $post)) {
            $icons = $this->textToPairs((string) $post['chip_icons']);
            $options['icons'] = $icons;
            if (!$icons) {
                unset($options['icons']);
            }
        }
        if (array_key_exists('chip_tints', $post)) {
            $tints = [];
            foreach ($this->textToPairs((string) $post['chip_tints']) as $key => $value) {
                if (preg_match('/^\d+$/', $value)) {
                    $tints[$key] = ((int) $value) % 8;
                }
            }
            $options['tints'] = $tints;
            if (!$tints) {
                unset($options['tints']);
            }
        }
        if (array_key_exists('tile_limit', $post)) {
            $raw = trim((string) $post['tile_limit']);
            if ($raw === '') {
                unset($options['tile_limit']);
            } else {
                $options['tile_limit'] = max(0, min(6, (int) $raw));
            }
        }
        foreach (['badge_en', 'badge_ar'] as $key) {
            if (array_key_exists($key, $post)) {
                $badge = $this->text($post[$key], self::MAX_BADGE);
                if ($badge === null) {
                    unset($options[$key]);
                } else {
                    $options[$key] = $badge;
                }
            }
        }
        if (array_key_exists('featured_only', $post)) {
            if ($this->flag($post['featured_only'])) {
                $options['featured_only'] = true;
            } else {
                unset($options['featured_only']);
            }
        }

        if (!$options) {
            return null;
        }

        //  Default flags: non-ASCII is written as \uXXXX, which a utf8 (3-byte)
        //  column stores safely even for emoji.
        return (string) json_encode($options);
    }

    private function text(mixed $value, int $max): ?string
    {
        if (is_array($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }

    private function positiveInt(mixed $value): ?int
    {
        if (is_array($value)) {
            $value = reset($value);
        }
        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function codes(mixed $value): ?string
    {
        $list = is_array($value)
            ? SectionContext::splitList(implode(',', array_map('strval', $value)))
            : SectionContext::splitList((string) $value);
        if (!$list) {
            return null;
        }
        $joined = implode(',', $list);
        if (strlen($joined) > self::MAX_CODES) {
            throw new LocalizedException(__('Too many sellers for one section.'));
        }

        return $joined;
    }

    private function skus(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = implode("\n", array_map('strval', $value));
        }
        $list = SectionContext::splitList((string) $value, '/[\r\n,]+/u');

        return $list ? implode("\n", $list) : null;
    }

    private function limit(mixed $value): int
    {
        $limit = (int) $value;
        if ($limit < 1) {
            return 8;
        }

        return min($limit, SectionContext::MAX_LIMIT);
    }

    private function sort(mixed $value): ?string
    {
        $sort = strtoupper(trim((string) $value));
        $allowed = array_merge(
            ProductSort::PRODUCT_SORTS,
            [ProductSort::FEATURED, ProductSort::NAME, ProductSort::PRODUCT_COUNT]
        );

        return in_array($sort, $allowed, true) ? $sort : null;
    }

    private function audience(mixed $value): string
    {
        $audience = strtolower(trim((string) $value));

        return in_array($audience, Section::AUDIENCES, true) ? $audience : Section::AUDIENCE_ALL;
    }

    private function flag(mixed $value): int
    {
        if (is_string($value)) {
            $value = strtolower(trim($value));

            return in_array($value, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
        }

        return $value ? 1 : 0;
    }

    /**
     * A date from the admin form as a UTC "Y-m-d H:i:s", or null.
     *
     * The UI date field (showsTime) shows store time and posts ISO-8601 UTC
     * ("2026-10-01T21:00:00.000Z"); an untouched field re-posts the stored UTC
     * value. Both parse here with UTC as the default zone.
     */
    public function utc(mixed $value, string $field = 'date'): ?string
    {
        if (is_array($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            throw new LocalizedException(__('"%1" is not a date: %2', $field, $value));
        }

        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * "key=value" lines (":" also accepted) -> map, keys lower-cased url keys.
     *
     * @return array<string, string>
     */
    public function textToPairs(string $text): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = preg_split('/\s*[=:]\s*/u', $line, 2) ?: [];
            if (count($parts) !== 2) {
                continue;
            }
            $key = strtolower(trim($parts[0]));
            $value = trim($parts[1]);
            if ($key !== '' && $value !== '') {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $pairs
     */
    private function pairsToText(array $pairs): string
    {
        $lines = [];
        foreach ($pairs as $key => $value) {
            $lines[] = $key . '=' . (is_scalar($value) ? (string) $value : '');
        }

        return implode("\n", $lines);
    }
}
