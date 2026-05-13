<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for slide search results.
 * @api
 */
interface ItemSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get slides list.
     *
     * @return \Vnecoms\BannerManager\Api\Data\ItemInterface[]
     */
    public function getItems();

    /**
     * Set slides list.
     *
     * @param \Vnecoms\BannerManager\Api\Data\ItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
