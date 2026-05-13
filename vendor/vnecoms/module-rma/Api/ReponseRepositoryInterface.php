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
interface ReponseRepositoryInterface
{
    /**
     * Save reponse.
     *
     * @param \Vnecoms\RMA\Api\Data\ReponseInterface $template
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\ReponseInterface $tyoe);

    /**
     * Retrieve Reponse.
     *
     * @param int $reponseId
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($reponseId);

    /**
     * Retrieve reponse matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\RMA\Api\Data\ReponseSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete reponse.
     *
     * @param \Vnecoms\RMA\Api\Data\ReponseInterface $reponse
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\ReponseInterface $reponse);

    /**
     * Delete Reponse by ID.
     *
     * @param int $reponseId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($reponseId);
}
