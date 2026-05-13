<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;
use Vnecoms\BannerManager\Api\Data\BlockInterface;

/**
 * Interface for Rbslider block search results
 *
 * @api
 */
interface BlockSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get blocks list
     *
     * @return BlockInterface[]
     */
    public function getItems();

    /**
     * Set blocks list
     *
     * @param BlockInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
