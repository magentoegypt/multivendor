<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * CMS page CRUD interface.
 * @api
 */
interface MessageRepositoryInterface
{

    /**
     * Retrieve Message.
     *
     * @param int $messageId
     * @return \Vnecoms\RMA\Api\Data\MesssagInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($messageId);

    /**
     * Retrieve Messsages matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @param int $ticketId
     * @return \Vnecoms\RMA\Api\Data\MessageSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria, $ticketId);


    /**
     * Retrieve Messsages matching the specified criteria.
     * @param \Vnecoms\RMA\Api\Data\MessageInterface $message
     * @param string $token
     * @param int $ticketId
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Vnecoms\RMA\Api\Data\MessageInterface $message, $token, $ticketId);
}
