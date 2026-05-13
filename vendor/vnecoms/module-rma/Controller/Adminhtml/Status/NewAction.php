<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Status;

use Vnecoms\RMA\Controller\Adminhtml\Status\AbstractStatus;

class NewAction extends AbstractStatus
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
