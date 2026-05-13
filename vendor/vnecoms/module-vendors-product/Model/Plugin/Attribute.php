<?php
namespace Vnecoms\VendorsProduct\Model\Plugin;

class Attribute
{
    /**
     * Vendor Product helper
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $productHelper;

    /**
     * @param \Vnecoms\VendorsProduct\Helper\Data $helper
     */
    public function __construct(
        \Vnecoms\VendorsProduct\Helper\Data $productHelper
    ) {
        $this->productHelper = $productHelper;
        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $subject
     * @param \Closure $proceed
     * @param $addRequiredCodes
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function aroundAddToIndexFilter(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $subject,
        \Closure $proceed,
        $addRequiredCodes
    ) {

        $attributes = $this->productHelper->getAdditionElasticAttribute();
        $attributes["status"] = "status";
        $attributes["visibility"] = "visibility";

        $conditions = [
            'additional_table.is_searchable = 1',
            'additional_table.is_visible_in_advanced_search = 1',
            'additional_table.is_filterable > 0',
            'additional_table.is_filterable_in_search = 1',
            'additional_table.used_for_sort_by = 1',
        ];

        if ($addRequiredCodes) {
            $conditions[] = $subject->getConnection()->quoteInto(
                'main_table.attribute_code IN (?)',
                array_keys($attributes)
            );
        }

        $subject->getSelect()->where(sprintf('(%s)', implode(' OR ', $conditions)));

        return $subject;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $subject
     * @param \Closure $proceed
     * @param $addRequiredCodes
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function aroundAddSearchableAttributeFilter(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $subject,
        \Closure $proceed
    ) {
        $attributes = $this->productHelper->getAdditionElasticAttribute();
        $attributes["status"] = "status";
        $attributes["visibility"] = "visibility";

        $subject->getSelect()->where(
            'additional_table.is_searchable = 1 OR ' . $subject->getConnection()->quoteInto(
                'main_table.attribute_code IN (?)',
                array_keys($attributes)
            )
        );
        return $subject;
    }
}
