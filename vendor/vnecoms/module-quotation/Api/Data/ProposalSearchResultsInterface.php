<?php


namespace Vnecoms\Quotation\Api\Data;

interface ProposalSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get Proposal list.
     * @return \Vnecoms\Quotation\Api\Data\ProposalInterface[]
     */
    
    public function getItems();

    /**
     * Set qty list.
     * @param \Vnecoms\Quotation\Api\Data\ProposalInterface[] $items
     * @return $this
     */
    
    public function setItems(array $items);
}
