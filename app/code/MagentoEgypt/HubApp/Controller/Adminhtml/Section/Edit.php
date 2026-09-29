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
use MagentoEgypt\HubApp\Model\Home\SectionFactory;

/**
 * Section form (Figma G2b). The form's data provider loads the record itself
 * from the section_id request parameter; this only checks it exists and sets
 * the page title.
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HubApp::home_sections';

    public function __construct(
        Action\Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly SectionFactory $sectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $id = (int) $this->getRequest()->getParam('section_id');
        $title = __('New Home Section');

        if ($id) {
            $section = $this->sectionFactory->create()->load($id);
            if (!$section->getId()) {
                $this->messageManager->addErrorMessage(__('This section no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
            $label = trim((string) ($section->getData('title_en') ?: $section->getData('type')));
            $title = __('Home Section #%1: %2', $id, $label);
        }

        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('MagentoEgypt_HubApp::home_sections');
        $page->getConfig()->getTitle()->prepend($title);

        return $page;
    }
}
