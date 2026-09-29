<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Controller\Adminhtml\Section;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use MagentoEgypt\HubApp\Api\CacheTagCleanerInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\ResourceModel\Section as SectionResource;
use MagentoEgypt\HubApp\Model\ResourceModel\Section\CollectionFactory;

/**
 * Enables (status=1) or disables (status=0) the selected sections: one UPDATE, one purge.
 */
class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HubApp::home_sections';

    public function __construct(
        Action\Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ResourceConnection $resource,
        private readonly CacheTagCleanerInterface $tagCleaner
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $status = (int) $this->getRequest()->getParam('status') === 1 ? 1 : 0;

        try {
            $ids = array_map('intval', $this->filter->getCollection($this->collectionFactory->create())->getAllIds());
            if (!$ids) {
                $this->messageManager->addErrorMessage(__('Select at least one section.'));

                return $redirect;
            }
            $changed = $this->resource->getConnection()->update(
                $this->resource->getTableName(SectionResource::TABLE),
                ['is_active' => $status],
                ['section_id IN (?)' => $ids]
            );
            $this->tagCleaner->clean(array_merge([Tags::APP_HOME], array_map([Tags::class, 'homeSection'], $ids)));
            $this->messageManager->addSuccessMessage($status
                ? __('%1 section(s) enabled.', $changed)
                : __('%1 section(s) disabled.', $changed));
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Could not change the sections.'));
        }

        return $redirect;
    }
}
