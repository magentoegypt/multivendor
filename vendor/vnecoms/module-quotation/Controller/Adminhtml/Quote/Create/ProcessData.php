<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

use Magento\Framework\App\Action\HttpPostActionInterface;

class ProcessData extends \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create implements HttpPostActionInterface
{
    /**
     * Process data and display index page
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $this->_initSession();
        $this->_processData();
        return $this->resultForwardFactory->create()->forward('index');
    }
}
