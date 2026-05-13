<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface MessageRepositoryInterface
{

    /**
     * Save Message
     * @param \Vnecoms\Quotation\Api\Data\MessageInterface $message
     * @return \Vnecoms\Quotation\Api\Data\MessageInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function save(
        \Vnecoms\Quotation\Api\Data\MessageInterface $message
    );

    /**
     * Retrieve Message
     * @param string $messageId
     * @return \Vnecoms\Quotation\Api\Data\MessageInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getById($messageId);

    /**
     * Retrieve Message matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\Quotation\Api\Data\MessageSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Message
     * @param \Vnecoms\Quotation\Api\Data\MessageInterface $message
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function delete(
        \Vnecoms\Quotation\Api\Data\MessageInterface $message
    );

    /**
     * Delete Message by ID
     * @param string $messageId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function deleteById($messageId);
}
