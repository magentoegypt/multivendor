<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * CMS block CRUD interface.
 * @api
 */
interface QueueRepositoryInterface
{
    /**
     * Save Template.
     *
     * @param \Vnecoms\HelpDesk\Api\Data\QueueInterface $queue
     * @return \Vnecoms\HelpDesk\Api\Data\QueueInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\QueueInterface $queue);

    /**
     * Retrieve Queue.
     *
     * @param int $queueId
     * @return \Vnecoms\HelpDesk\Api\Data\SpamInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($queueId);

    /**
     * Retrieve blocks matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\HelpDesk\Api\Data\TemplateSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete Template.
     *
     * @param \Vnecoms\HelpDesk\Api\Data\QueueInterface $template
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\QueueInterface $spam);

    /**
     * Delete queue by ID.
     *
     * @param int $queueId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($queueId);
}
