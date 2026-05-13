<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api;

use Vnecoms\BannerManager\Api\Data\BlockSearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * BannerManager block repository interface
 *
 * @api
 */
interface BlockRepositoryInterface
{
    /**
     * Retrieve block(s) matching the specified blockType and blockPosition
     * Update views block statistics if necessary
     *
     * @param int $blockType
     * @param int $blockPosition
     * @param bool $allBlocks
     * @param bool $updateViewsStatistic
     *
     * @return BlockSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList($blockType, $blockPosition = null, $allBlocks = false, $updateViewsStatistic = true);
}
