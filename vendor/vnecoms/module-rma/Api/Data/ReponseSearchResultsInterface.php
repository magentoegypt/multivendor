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
interface ReponseSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Reponses list.
     *
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface[]
     */
    public function getItems();

    /**
     * Set Reponses list.
     *
     * @param \Vnecoms\RMA\Api\Data\ReponseInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
