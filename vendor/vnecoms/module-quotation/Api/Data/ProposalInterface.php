<?php


namespace Vnecoms\Quotation\Api\Data;

interface ProposalInterface
{

    const PROPOSAL_ID = 'proposal_id';
    const QTY = 'qty';


    /**
     * Get proposal_id
     * @return string|null
     */
    
    public function getProposalId();

    /**
     * Set proposal_id
     * @param string $proposal_id
     * @return \Vnecoms\Quotation\Api\Data\ProposalInterface
     */
    
    public function setProposalId($proposalId);

    /**
     * Get qty
     * @return string|null
     */
    
    public function getQty();

    /**
     * Set qty
     * @param string $qty
     * @return \Vnecoms\Quotation\Api\Data\ProposalInterface
     */
    
    public function setQty($qty);
}
