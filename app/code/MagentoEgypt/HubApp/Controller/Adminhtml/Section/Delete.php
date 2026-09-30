<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Controller\Adminhtml\Section;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use MagentoEgypt\HubApp\Model\Home\SectionFactory;

/**
 * POST only: a GET delete endpoint is reachable from any link or prefetch.
 * The grid action and the form's Delete button both post.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HubApp::home_sections';

    public function __construct(
        Action\Context $context,
        private readonly SectionFactory $sectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $id = (int) $this->getRequest()->getParam('section_id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('Could not find a section to delete.'));

            return $redirect;
        }

        try {
            $section = $this->sectionFactory->create()->load($id);
            if (!$section->getId()) {
                $this->messageManager->addErrorMessage(__('This section no longer exists.'));

                return $redirect;
            }
            $section->delete();
            $this->messageManager->addSuccessMessage(__('The section has been deleted.'));
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Could not delete the section.'));
        }

        return $redirect;
    }
}
