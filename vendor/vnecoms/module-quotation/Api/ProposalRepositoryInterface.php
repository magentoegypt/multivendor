<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ProposalRepositoryInterface
{
    /**
     * Save Proposal
     * @param \Vnecoms\Quotation\Api\Data\ProposalInterface $proposal
     * @return \Vnecoms\Quotation\Api\Data\ProposalInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function save(
        \Vnecoms\Quotation\Api\Data\ProposalInterface $proposal
    );

    /**
     * Retrieve Proposal
     * @param string $proposalId
     * @return \Vnecoms\Quotation\Api\Data\ProposalInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getById($proposalId);

    /**
     * Retrieve Proposal matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\Quotation\Api\Data\ProposalSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Proposal
     * @param \Vnecoms\Quotation\Api\Data\ProposalInterface $proposal
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function delete(
        \Vnecoms\Quotation\Api\Data\ProposalInterface $proposal
    );

    /**
     * Delete Proposal by ID
     * @param string $proposalId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function deleteById($proposalId);
}
