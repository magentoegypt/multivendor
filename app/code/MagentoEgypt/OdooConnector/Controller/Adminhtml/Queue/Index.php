<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Controller\Adminhtml\Queue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_OdooConnector::monitor';

    private PageFactory $resultPageFactory;

    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): ResultInterface
    {
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('MagentoEgypt_OdooConnector::queue');
        $page->getConfig()->getTitle()->prepend(__('Odoo Sync — Queue'));

        return $page;
    }
}
