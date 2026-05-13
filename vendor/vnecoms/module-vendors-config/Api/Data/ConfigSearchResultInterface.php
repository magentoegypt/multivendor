<?php

namespace Vnecoms\VendorsConfig\Api\Data;


interface ConfigSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return \Vnecoms\VendorsApi\Api\Data\NotificationInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param \Vnecoms\VendorsApi\Api\Data\NotificationInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
