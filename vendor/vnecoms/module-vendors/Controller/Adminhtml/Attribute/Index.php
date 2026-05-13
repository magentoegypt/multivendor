<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Controller\Adminhtml\Attribute;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends \Vnecoms\Vendors\Controller\Adminhtml\Attribute implements HttpGetActionInterface
{
    /**
     * Is access to section allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return parent::_isAllowed() && $this->_authorization->isAllowed('Vnecoms_Vendors::vendors_attributes');
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Vnecoms_Vendors::vendors_attributes');
        $resultPage->getConfig()->getTitle()->prepend(__('Seller Attributes'));
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock('Vnecoms\Vendors\Block\Adminhtml\Attribute')
        );
        return $resultPage;
    }
}
