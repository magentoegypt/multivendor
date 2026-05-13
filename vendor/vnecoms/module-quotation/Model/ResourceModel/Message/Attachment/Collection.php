<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model\ResourceModel\Message\Attachment;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\Quotation\Model\Message\Attachment',
            'Vnecoms\Quotation\Model\ResourceModel\Message\Attachment'
        );
    }
}
