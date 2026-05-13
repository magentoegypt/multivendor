<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model\ResourceModel;

class Item extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vnecoms_quotation_item', 'item_id');
    }

    /**
     * @param $quoteId
     * @return array
     */
    public function getItemsByQuote($quoteId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            '*'
        )->where(
            'quote_id = :quote_id'
        );
        $bind = ['quote_id' => (int) $quoteId];

        return $this->getConnection()->fetchPairs($select, $bind);
    }

    /**
     * @param $itemId
     * @param $defaultProposal
     */
    public function updateDefaultProposal($itemId, $defaultProposal)
    {
        $bind = ['default_proposal' => $defaultProposal];
        $this->getConnection()->update($this->getMainTable(),
            $bind,
            $this->getConnection()->quoteInto('item_id = ?', $itemId)
        );
    }
}
