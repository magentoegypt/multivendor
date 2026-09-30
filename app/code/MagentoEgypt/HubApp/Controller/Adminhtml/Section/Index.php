<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Controller\Adminhtml\Section;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Content > Elements > App Home Sections (Figma G2a).
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HubApp::home_sections';

    public function __construct(
        Action\Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('MagentoEgypt_HubApp::home_sections');
        $page->getConfig()->getTitle()->prepend(__('App Home Sections'));

        return $page;
    }
}
