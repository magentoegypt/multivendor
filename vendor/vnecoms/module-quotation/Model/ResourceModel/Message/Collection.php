<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model\ResourceModel\Message;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_quote;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\Quotation\Model\Message',
            'Vnecoms\Quotation\Model\ResourceModel\Message'
        );
    }

    /**
     * Retrieve store Id (From Quote)
     *
     * @return int
     */
    public function getStoreId()
    {
        return (int)$this->_quote->getStoreId();
    }

    /**
     * Add quote filter
     *
     * @param int|\Vnecoms\Quotation\Model\Quote|array $quote
     * @return $this
     */
    public function setQuoteFilter($quote)
    {
        $this->_quote = $quote;
        if ($quote instanceof \Vnecoms\Quotation\Model\Quote) {
            $quoteId = $quote->getId();
            if ($quoteId) {
                $this->addFieldToFilter('quote_id', $quoteId);
            } else {
                $this->_totalRecords = 0;
                $this->_setIsLoaded(true);
            }
        } else {
            $this->addFieldToFilter('quote_id', $quote);
        }
        return $this;
    }

    /**
     * After load processing
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        /**
         * Assign parent items
         */
        foreach ($this as $item) {
            if ($this->_quote) {
                $item->setQuote($this->_quote);
            }
        }
        $this->resetItemsDataChanged();

        return $this;
    }

    /**
     * @param array $arrRequiredFields
     * @return array
     */
    public function toArray($arrRequiredFields = [])
    {
        $arrItems = [];
        $arrItems['totalRecords'] = $this->getSize();

        $arrItems['items'] = [];
        foreach ($this as $item) {
            $arrItems['items'][] = $item->toArray($arrRequiredFields);
        }
        return $arrItems;
    }
}
