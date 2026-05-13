<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model\ResourceModel;

class Message extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vnecoms_quotation_message', 'message_id');
    }


    /**
     * @param $quoteId
     * @return array
     */
    public function getMessagesByQuote($quoteId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            '*'
        )->where(
            'entity_id = :quote_id'
        );
        $bind = ['quote_id' => (int) $quoteId];

        return $this->getConnection()->fetchPairs($select, $bind);
    }
}
