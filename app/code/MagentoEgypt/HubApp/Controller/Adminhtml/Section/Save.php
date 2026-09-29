<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Controller\Adminhtml\Section;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionFactory;
use MagentoEgypt\HubApp\Model\Section\DataProvider;
use MagentoEgypt\HubApp\Model\Section\FormDataMapper;

/**
 * Saves a Home section. Only the whitelisted columns of FormDataMapper::toRow()
 * reach the model; the save purges the cached app Homes through the model's
 * identities (see Model\Home\Section).
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HubApp::home_sections';

    public function __construct(
        Action\Context $context,
        private readonly SectionFactory $sectionFactory,
        private readonly FormDataMapper $mapper,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create();
        $post = $this->getRequest()->getPostValue();
        if (!is_array($post) || !$post) {
            return $redirect->setPath('*/*/');
        }

        $id = (int) ($post['section_id'] ?? 0);
        $section = $this->sectionFactory->create();
        if ($id) {
            $section->load($id);
            if (!$section->getId()) {
                $this->messageManager->addErrorMessage(__('This section no longer exists.'));

                return $redirect->setPath('*/*/');
            }
        }

        try {
            $row = $this->mapper->toRow($post, SectionContext::decodeOptions($section->getData('options')));
            $section->addData($row)->save();
            $this->dataPersistor->clear(DataProvider::PERSISTOR_KEY);
            $this->messageManager->addSuccessMessage(__('The section has been saved. The app shows it on its next Home request.'));

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['section_id' => $section->getId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Could not save the section.'));
        }

        //  Back to the form with what was typed, rather than an empty form.
        $this->dataPersistor->set(DataProvider::PERSISTOR_KEY, $post);

        return $id
            ? $redirect->setPath('*/*/edit', ['section_id' => $id])
            : $redirect->setPath('*/*/new');
    }
}
