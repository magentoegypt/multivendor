<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * The admin "Sort" select of a Home section.
 *
 * One list for product and seller sections, because the values the two share
 * mean the same thing (NEWEST, TOP_RATED) and the seller values are the
 * HmStoreSort names HubAppVendors reads. A provider ignores a value that does
 * not apply to its type and uses its own default.
 */
class ProductSort implements OptionSourceInterface
{
    public const NEWEST = 'NEWEST';
    public const POSITION = 'POSITION';
    public const PRICE_ASC = 'PRICE_ASC';
    public const PRICE_DESC = 'PRICE_DESC';
    public const TOP_RATED = 'TOP_RATED';
    public const BEST_SELLING = 'BEST_SELLING';

    /** Seller sections only (HmStoreSort). */
    public const FEATURED = 'FEATURED';
    public const NAME = 'NAME';
    public const PRODUCT_COUNT = 'PRODUCT_COUNT';

    public const PRODUCT_SORTS = [
        self::NEWEST,
        self::POSITION,
        self::PRICE_ASC,
        self::PRICE_DESC,
        self::TOP_RATED,
        self::BEST_SELLING,
    ];

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase|string}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('Default for the section type')],
            ['value' => self::NEWEST, 'label' => __('Newest first')],
            ['value' => self::TOP_RATED, 'label' => __('Top rated')],
            ['value' => self::POSITION, 'label' => __('Category position (products)')],
            ['value' => self::PRICE_ASC, 'label' => __('Price: low to high (products)')],
            ['value' => self::PRICE_DESC, 'label' => __('Price: high to low (products)')],
            ['value' => self::BEST_SELLING, 'label' => __('Best selling (products)')],
            ['value' => self::FEATURED, 'label' => __('Featured first (sellers)')],
            ['value' => self::NAME, 'label' => __('Name A-Z (sellers)')],
            ['value' => self::PRODUCT_COUNT, 'label' => __('Most products (sellers)')],
        ];
    }
}
