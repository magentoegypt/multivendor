<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MagentoEgypt\HeroBanner\Model\Banner;

class Slot implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Banner::SLOT_HERO, 'label' => __('Carousel slide (large, left)')],
            ['value' => Banner::SLOT_TILE, 'label' => __('Side tile (small, right)')],
        ];
    }
}
