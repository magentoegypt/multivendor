<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for template search results.
 * @api
 */
interface StatusSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Statuss list.
     *
     * @return \Vnecoms\RMA\Api\Data\StatusInterface[]
     */
    public function getItems();

    /**
     * Set Statuss list.
     *
     * @param \Vnecoms\RMA\Api\Data\StatusInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
