<?php

namespace Vnecoms\VendorsProductImportExport\Api\Data;


interface ImageSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface[] Array of collection items.
     */
    public function getItems();
    
    /**
     * Set items.
     *
     * @param \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
