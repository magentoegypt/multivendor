<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Controller\Vendors\Template;

use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\Vendors\Controller\Vendors\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\PageBuilder\Api\Data\TemplateInterface;
use Magento\PageBuilder\Api\TemplateRepositoryInterface;
use Magento\Backend\Model\View\Result\Redirect;
use Psr\Log\LoggerInterface;

/**
 * Delete a template within template manager
 */
class Delete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsPageBuilder::template';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TemplateRepositoryInterface
     */
    private $templateRepository;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param TemplateRepositoryInterface $templateRepository
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        TemplateRepositoryInterface $templateRepository
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Delete a template from the database
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $request = $this->getRequest();

        try {
            $template = $this->templateRepository->get($request->getParam(TemplateInterface::KEY_ID));
            $vendor = $this->_getSession()->getVendor();

            if ($template->getId()) {
                if ($template->getVendorId() != $vendor->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('You can\'t delete this Template.'));
                }
            }

            $this->templateRepository->deleteById($request->getParam(TemplateInterface::KEY_ID));

            $this->messageManager->addSuccessMessage(__('Template successfully deleted.'));
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->logger->critical($e);

            $this->messageManager->addErrorMessage(__('An error occurred while trying to delete this template.'));
            return $resultRedirect->setPath('*/*/');
        }
    }
}
