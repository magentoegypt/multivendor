<?php

namespace Vnecoms\VendorsSearch\Model\Layer\Search;

class FilterableAttributeList extends \Magento\Catalog\Model\Layer\Search\FilterableAttributeList
{
    public function getList()
    {
        $collection = parent::getList();
        return $collection;
    }
}