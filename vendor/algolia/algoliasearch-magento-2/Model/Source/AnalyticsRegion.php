<?php

namespace Algolia\AlgoliaSearch\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class AnalyticsRegion implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'us', 'label' => __('United States')],
            ['value' => 'de', 'label' => __('Europe (Germany)')],
        ];
    }
}
