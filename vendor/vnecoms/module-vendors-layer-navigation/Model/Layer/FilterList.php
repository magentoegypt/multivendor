<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsLayerNavigation\Model\Layer;


class FilterList extends \Magento\Catalog\Model\Layer\FilterList
{
    /**
     * @var string[]
     */
    protected $filterTypes = [
        self::CATEGORY_FILTER  => 'Magento\Catalog\Model\Layer\Filter\Category',
        self::ATTRIBUTE_FILTER => 'Magento\Catalog\Model\Layer\Filter\Attribute',
        self::PRICE_FILTER     => 'Magento\Catalog\Model\Layer\Filter\Price',
        self::DECIMAL_FILTER   => 'Magento\Catalog\Model\Layer\Filter\Decimal',
    ];

    /**
     * Retrieve list of filters
     *
     * @param \Magento\Catalog\Model\Layer $layer
     * @return array|Filter\AbstractFilter[]
     */
    public function getFilters(\Magento\Catalog\Model\Layer $layer)
    {
        if (!count($this->filters)) {
            if (isset($this->filterTypes['vendor_category'])) {
                $this->filters = [
                    $this->objectManager->create($this->filterTypes['vendor_category'], ['layer' => $layer]),
                ];
            }

            foreach ($this->filterableAttributes->getList() as $attribute) {
                $this->filters[] = $this->createAttributeFilter($attribute, $layer);
            }
        }
        return $this->filters;
    }
}
