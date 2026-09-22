<?php

namespace Algolia\AlgoliaSearch\Api;

use Algolia\AlgoliaSearch\Api\Data\QueueArchiveInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface QueueArchiveRepositoryInterface
 * @package Algolia\AlgoliaSearch\Api
 */
interface QueueArchiveRepositoryInterface
{
    /**
     * Save queue archive
     *
     * @param QueueArchiveInterface $queueArchive
     * @return QueueArchiveInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(QueueArchiveInterface $queueArchive);

    /**
     * Retrieve queue archive by id
     *
     * @param int $id
     * @return QueueArchiveInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve queue archives matching the specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete queue archive
     *
     * @param QueueArchiveInterface $queueArchive
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(QueueArchiveInterface $queueArchive);

    /**
     * Delete queue archive by ID
     *
     * @param int $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
