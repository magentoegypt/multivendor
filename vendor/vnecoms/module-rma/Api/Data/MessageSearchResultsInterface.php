<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for email gateway search results.
 * @api
 */
interface MessageSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get pages list.
     *
     * @return \Vnecoms\RMA\Api\Data\MessageInterface[]
     */
    public function getItems();

    /**
     * Set pages list.
     *
     * @param \Vnecoms\RMA\Api\Data\MessageInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
