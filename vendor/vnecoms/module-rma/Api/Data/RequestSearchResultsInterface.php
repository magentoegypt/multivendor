<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for request search results.
 * @api
 */
interface RequestSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get requests list.
     *
     * @return \Vnecoms\RMA\Api\Data\RequestInterface[]
     */
    public function getItems();

    /**
     * Set requests list.
     *
     * @param \Vnecoms\RMA\Api\Data\RequestInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
