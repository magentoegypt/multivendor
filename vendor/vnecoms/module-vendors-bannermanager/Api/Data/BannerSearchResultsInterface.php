<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for banner search results.
 * @api
 */
interface BannerSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get banners list.
     *
     * @return \Vnecoms\BannerManager\Api\Data\BannerInterface[]
     */
    public function getItems();

    /**
     * Set banners list.
     *
     * @param \Vnecoms\BannerManager\Api\Data\BannerInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
