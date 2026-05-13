<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ItemRepositoryInterface
{


    /**
     * Save Item
     * @param \Vnecoms\Quotation\Api\Data\ItemInterface $item
     * @return \Vnecoms\Quotation\Api\Data\ItemInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function save(
        \Vnecoms\Quotation\Api\Data\ItemInterface $item
    );

    /**
     * Retrieve Item
     * @param string $itemId
     * @return \Vnecoms\Quotation\Api\Data\ItemInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getById($itemId);

    /**
     * Retrieve Item matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\Quotation\Api\Data\ItemSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Item
     * @param \Vnecoms\Quotation\Api\Data\ItemInterface $item
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function delete(
        \Vnecoms\Quotation\Api\Data\ItemInterface $item
    );

    /**
     * Delete Item by ID
     * @param string $itemId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    public function deleteById($itemId);

    /**
     * Get Items By Quote
     * @param string $quoteId
     * @return \Vnecoms\Quotation\Model\Item[]
     */
    public function getItemsByQuote($quoteId);
}
