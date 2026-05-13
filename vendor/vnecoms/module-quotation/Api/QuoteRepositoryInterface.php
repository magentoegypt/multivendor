<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vnecoms\Quotation\Api\Data\QuoteInterface;

interface QuoteRepositoryInterface
{
    /**
     * Save Quote
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function save(
        \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
    );

    /**
     * Retrieve Quote
     * @param string $quoteId
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getById($quoteId);

    /**
     * Retrieve Quote matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\Quotation\Api\Data\QuoteSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Quote
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function delete(
        \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
    );

    /**
     * Delete Quote by ID
     * @param string $quoteId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function deleteById($quoteId);

    /**
     * @param string $customerId
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getForCustomer($customerId);

    /**
     * @param $customerId
     * @return \Vnecoms\Quotation\Model\Quote| null
     */
    public function getActiveForCustomer($customerId);

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function hold(QuoteInterface $quote);

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function unHold(QuoteInterface $quote);

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function cancel(QuoteInterface $quote);

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function reject(QuoteInterface $quote);

    /**
     * @param QuoteInterface $quote
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function accept(QuoteInterface $quote);
    
    /**
     * @param QuoteInterface $quote
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function submit(QuoteInterface $quote);
}
