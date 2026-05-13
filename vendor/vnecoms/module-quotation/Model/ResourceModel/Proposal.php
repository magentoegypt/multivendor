<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model\ResourceModel;

class Proposal extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vnecoms_quotation_item_proposal', 'proposal_id');
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);

        if($object->getIsDefault()){
            $this->updateDefaultProposal($object);
        }
        return $this;
    }

    /**
     * Update default proposal for quote item.
     * 
     * @param \Vnecoms\Quotation\Model\Proposal $object
     */
    public function updateDefaultProposal($object)
    {
        $bind = ['default_proposal' => $object->getId()];
        $this->getConnection()->update($this->getTable('vnecoms_quotation_item'),
            $bind,
            $this->getConnection()->quoteInto('item_id = ?', $object->getItemId())
        );
    }
}
