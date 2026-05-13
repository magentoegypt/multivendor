<?php


namespace Vnecoms\Quotation\Api\Data;

interface ItemSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get Item list.
     * @return \Vnecoms\Quotation\Api\Data\ItemInterface[]
     */
    
    public function getItems();

    /**
     * Set quote_id list.
     * @param \Vnecoms\Quotation\Api\Data\ItemInterface[] $items
     * @return $this
     */
    
    public function setItems(array $items);
}
