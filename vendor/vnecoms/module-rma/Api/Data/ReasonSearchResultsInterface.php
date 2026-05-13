<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:39 AM
 */
namespace Vnecoms\RMA\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for template search results.
 * @api
 */
interface ReasonSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Reason list.
     *
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface[]
     */
    public function getItems();

    /**
     * Set Reasons list.
     *
     * @param \Vnecoms\RMA\Api\Data\ReasonInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
