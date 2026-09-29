<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

/**
 * What a provider built for one section. Immutable; build it with the with*() methods:
 *
 *   return SectionResult::create()
 *       ->withField('stores', $cards)
 *       ->withTags([Tags::VENDOR]);
 *
 * - fields:      HmHomeSection content fields (banners, categories, cms_block,
 *                brands, bundles, stores, countdown_ends_at, …). The builder sets
 *                id / type / title / subtitle / limit / ends_at / more_link itself.
 * - productIds:  ranked, gated ids for the `products` field; loaded later for all
 *                sections in one collection.
 * - tags:        cache tags this content depends on beyond hm_app_home
 *                (cms_b_<id>, cat_c_<id>, hm_brand, hm_vendor, …). Product tags
 *                are added by the builder from productIds.
 * - defaultTitle / defaultMoreLink: used when the admin left title / more URL empty
 *                (e.g. a category rail falls back to the category's name and page).
 */
final class SectionResult
{
    /**
     * @param array<string, mixed> $fields
     * @param int[] $productIds
     * @param string[] $tags
     * @param array<string, string|null>|null $defaultMoreLink
     */
    private function __construct(
        private readonly array $fields = [],
        private readonly array $productIds = [],
        private readonly array $tags = [],
        private readonly ?string $defaultTitle = null,
        private readonly ?array $defaultMoreLink = null
    ) {
    }

    public static function create(): self
    {
        return new self();
    }

    public function withField(string $name, mixed $value): self
    {
        $fields = $this->fields;
        $fields[$name] = $value;

        return new self($fields, $this->productIds, $this->tags, $this->defaultTitle, $this->defaultMoreLink);
    }

    /**
     * @param int[] $productIds ranked ids, already gated and cut to the section limit
     */
    public function withProductIds(array $productIds): self
    {
        $ids = [];
        foreach ($productIds as $id) {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return new self($this->fields, $ids, $this->tags, $this->defaultTitle, $this->defaultMoreLink);
    }

    /**
     * @param string[] $tags
     */
    public function withTags(array $tags): self
    {
        $merged = array_values(array_unique(array_merge(
            $this->tags,
            array_values(array_filter(array_map('strval', $tags), static fn (string $t): bool => $t !== ''))
        )));

        return new self($this->fields, $this->productIds, $merged, $this->defaultTitle, $this->defaultMoreLink);
    }

    public function withDefaultTitle(?string $title): self
    {
        $title = $title !== null ? trim($title) : null;

        return new self(
            $this->fields,
            $this->productIds,
            $this->tags,
            $title !== '' ? $title : null,
            $this->defaultMoreLink
        );
    }

    /**
     * @param array<string, string|null>|null $link HmLink array
     */
    public function withDefaultMoreLink(?array $link): self
    {
        return new self($this->fields, $this->productIds, $this->tags, $this->defaultTitle, $link);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return int[]
     */
    public function getProductIds(): array
    {
        return $this->productIds;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getDefaultTitle(): ?string
    {
        return $this->defaultTitle;
    }

    /**
     * @return array<string, string|null>|null
     */
    public function getDefaultMoreLink(): ?array
    {
        return $this->defaultMoreLink;
    }

    /**
     * True when there is nothing to show: no products and every content field
     * empty. Such a section is omitted from the Home.
     */
    public function isEmpty(): bool
    {
        if ($this->productIds !== []) {
            return false;
        }
        foreach ($this->fields as $name => $value) {
            if ($name === 'countdown_ends_at') {
                continue;   // decoration of a product section, not content
            }
            if ($value !== null && $value !== [] && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
