<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Cancel extends \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create implements HttpGetActionInterface
{
    /**
     * Cancel quote create
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $this->_getSession()->clearStorage();
        $resultRedirect->setPath('quotation/*');

        return $resultRedirect;
    }
}
