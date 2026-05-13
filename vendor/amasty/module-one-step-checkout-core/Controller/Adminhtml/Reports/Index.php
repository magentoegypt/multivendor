<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Controller\Adminhtml\Reports;

use Magento\Backend\App\Action;

class Index extends Action
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Amasty_CheckoutCore::reports');
        $resultPage->getConfig()->getTitle()->prepend(__('Checkout Analytics'));

        return $resultPage;
    }

    /**
     * Determine if authorized to perform group action.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_CheckoutCore::reports');
    }
}
