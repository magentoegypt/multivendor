<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

/**
 * Everything a section provider may know: the admin row and the store view.
 *
 * Deliberately NOT the GraphQL context: the built Home is shared by every viewer
 * of a store view and audience, so a provider has no customer to read.
 *
 * Immutable. The row is the raw magentoegypt_hubapp_home_section row; the
 * getters normalise it (comma lists, JSON options, limits) so every provider
 * reads it the same way.
 */
final class SectionContext
{
    /** Hard ceiling for any section, whatever the admin typed. */
    public const MAX_LIMIT = 50;

    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $section raw table row
     */
    public function __construct(
        private readonly array $section,
        private readonly int $storeId,
        private readonly string $storeCode,
        private readonly int $websiteId,
        private readonly string $locale,
        private readonly string $audience,
        private readonly \DateTimeImmutable $now,
        private readonly string $timezone
    ) {
        $this->options = self::decodeOptions($section['options'] ?? null);
    }

    public function getSectionId(): int
    {
        return (int) ($this->section['section_id'] ?? 0);
    }

    /** HmSectionType value, e.g. TODAYS_DEALS. */
    public function getType(): string
    {
        return strtoupper(trim((string) ($this->section['type'] ?? '')));
    }

    /** Configured item limit, clamped to 1..MAX_LIMIT (8 when unset). */
    public function getLimit(): int
    {
        $limit = (int) ($this->section['item_limit'] ?? 0);
        if ($limit < 1) {
            $limit = 8;
        }

        return min($limit, self::MAX_LIMIT);
    }

    public function getCategoryId(): ?int
    {
        $id = (int) ($this->section['category_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * Seller codes in admin order, de-duplicated, case kept.
     *
     * @return string[]
     */
    public function getVendorCodes(): array
    {
        return self::splitList((string) ($this->section['vendor_codes'] ?? ''));
    }

    /**
     * Hand-picked SKUs in admin order (comma or line separated).
     *
     * @return string[]
     */
    public function getProductSkus(): array
    {
        // Commas and line breaks only: a SKU may contain spaces ("test new bundle").
        return self::splitList((string) ($this->section['product_skus'] ?? ''), '/[\r\n,]+/u');
    }

    public function getCmsIdentifier(): ?string
    {
        $id = trim((string) ($this->section['cms_identifier'] ?? ''));

        return $id !== '' ? $id : null;
    }

    /** Sort code from Model\Source\ProductSort (upper case), or null for the type's default. */
    public function getSortBy(): ?string
    {
        $sort = strtoupper(trim((string) ($this->section['sort_by'] ?? '')));

        return $sort !== '' ? $sort : null;
    }

    public function getMoreUrl(): ?string
    {
        $url = trim((string) ($this->section['more_url'] ?? ''));

        return $url !== '' ? $url : null;
    }

    /**
     * Decoded `options` JSON (chip order / icons / tints, hero tile_limit, …).
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * The raw row, for satellites that store extra settings.
     *
     * @return array<string, mixed>
     */
    public function getSection(): array
    {
        return $this->section;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getStoreCode(): string
    {
        return $this->storeCode;
    }

    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }

    /** Store view locale, e.g. ar_SA. */
    public function getLocale(): string
    {
        return $this->locale;
    }

    public function isArabic(): bool
    {
        return str_starts_with(strtolower($this->locale), 'ar');
    }

    /** GUEST or CUSTOMER — marketing targeting only, never authorisation. */
    public function getAudience(): string
    {
        return $this->audience;
    }

    /** Build time, UTC. */
    public function getNow(): \DateTimeImmutable
    {
        return $this->now;
    }

    /** Store timezone name, e.g. Asia/Riyadh. */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * Split a list typed in admin, trimmed and de-duplicated, order kept.
     *
     * @return string[]
     */
    public static function splitList(string $value, string $separator = '/[\s,;]+/u'): array
    {
        $parts = preg_split($separator, trim($value)) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && !in_array($part, $out, true)) {
                $out[] = $part;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeOptions(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }
        try {
            $decoded = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
