<?php
namespace MagentoEgypt\BundleExtend\Model\ResourceModel\Product;

class Collection extends \Magento\Bundle\Model\ResourceModel\Selection\Collection
{
    /**
     * Add filter by required options
     *
     * @return $this
     */
    public function addFilterByRequiredOptions()
    {
        return $this;
    }
}