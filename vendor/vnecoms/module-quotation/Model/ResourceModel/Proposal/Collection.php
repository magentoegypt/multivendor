<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model\ResourceModel\Proposal;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $item;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\Quotation\Model\Proposal',
            'Vnecoms\Quotation\Model\ResourceModel\Proposal'
        );
    }

    /**
     * Add item filter
     *
     * @param int|\Vnecoms\Quotation\Model\Item|array $item
     * @return $this
     */
    public function setItemFilter($item)
    {
        $this->item = $item;
        if ($item instanceof \Vnecoms\Quotation\Model\Item) {
            $itemId = $item->getId();
            if ($itemId) {
                $this->addFieldToFilter('item_id', $itemId);
            } else {
                $this->_totalRecords = 0;
                $this->_setIsLoaded(true);
            }
        } else {
            $this->addFieldToFilter('item_id', $item);
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
            if ($this->item)
                $item->setItem($this->item);
        }

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

        $arrItems['child'] = [];
        foreach ($this as $item) {
            $arrItems['child'][] = $item->toArray($arrRequiredFields);
        }
        return $arrItems;
    }
}
