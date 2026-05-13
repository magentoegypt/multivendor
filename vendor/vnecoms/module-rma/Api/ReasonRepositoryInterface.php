<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:42 AM
 */
namespace Vnecoms\RMA\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * CMS block CRUD interface.
 * @api
 */
interface ReasonRepositoryInterface
{
    /**
     * Save reason.
     *
     * @param \Vnecoms\RMA\Api\Data\ReasonInterface $template
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\ReasonInterface $tyoe);

    /**
     * Retrieve Reason.
     *
     * @param int $reasonId
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($reasonId);

    /**
     * Retrieve reason matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\RMA\Api\Data\ReasonSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete reason.
     *
     * @param \Vnecoms\RMA\Api\Data\ReasonInterface $reason
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\ReasonInterface $reason);

    /**
     * Delete Reason by ID.
     *
     * @param int $reasonId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($reasonId);
}
