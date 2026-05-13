<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Api\Data;

interface QuoteSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get Quote list.
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface[]
     */

    public function getItems();

    /**
     * Set increment_id list.
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface[] $items
     * @return $this
     */

    public function setItems(array $items);
}
