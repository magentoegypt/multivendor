<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for template search results.
 * @api
 */
interface TablerateSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Reponses list.
     *
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface[]
     */
    public function getItems();

    /**
     * Set Reponses list.
     *
     * @param \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
