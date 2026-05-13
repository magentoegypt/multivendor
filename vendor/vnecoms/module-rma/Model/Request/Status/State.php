<?php

namespace Vnecoms\RMA\Model\Request\Status;

use Magento\Framework\Model\AbstractModel;

class State extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Status\State');
    }
}
