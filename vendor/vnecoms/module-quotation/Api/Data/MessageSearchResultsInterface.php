<?php


namespace Vnecoms\Quotation\Api\Data;

interface MessageSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get Message list.
     * @return \Vnecoms\Quotation\Api\Data\MessageInterface[]
     */
    
    public function getItems();

    /**
     * Set name list.
     * @param \Vnecoms\Quotation\Api\Data\MessageInterface[] $items
     * @return $this
     */
    
    public function setItems(array $items);
}
