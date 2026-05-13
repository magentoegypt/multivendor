<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:42 AM
 */
namespace Vnecoms\VendorsShippingTableRate\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * CMS block CRUD interface.
 * @api
 */
interface TablerateRepositoryInterface
{
    /**
     * Save rate.
     * @param int $customerId
     * @param \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface $rate
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save($customerId, Data\TablerateInterface $rate);

    /**
     * Retrieve rate.
     *
     * @param int $rateId
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($rateId);

    /**
     * Retrieve reason matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete rate.
     *
     * @param \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface $rate
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\TablerateInterface $rate);

    /**
     * Delete rate by ID.
     * @param int $customerId
     * @param int $rateId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($customerId, $rateId);
}
