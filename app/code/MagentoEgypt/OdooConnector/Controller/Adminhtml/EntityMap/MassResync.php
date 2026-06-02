<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Controller\Adminhtml\EntityMap;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use MagentoEgypt\OdooConnector\Model\Queue\QueueManager;
use MagentoEgypt\OdooConnector\Model\ResourceModel\EntityMap\CollectionFactory;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;

/**
 * Mass action on the Entity Map grid: re-enqueue the selected mappings for a
 * Magento -> Odoo (m2o) update. Work is queued (never called inline), so the
 * cron consumer drains it with the normal retry/backoff. Idempotent: the queue
 * coalesces an identical pending row, so re-running does not duplicate work.
 */
class MassResync extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_OdooConnector::monitor';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private QueueManager $queue;
    private CorrelationId $correlationId;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        QueueManager $queue,
        CorrelationId $correlationId
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->queue = $queue;
        $this->correlationId = $correlationId;
    }

    public function execute(): ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/');
        }

        $enqueued = 0;
        $skipped = 0;

        foreach ($collection as $map) {
            $entityType = (string)$map->getData('entity_type');
            $magentoId = (string)$map->getData('magento_id');

            // m2o resync needs a Magento id; Odoo-origin rows with no Magento
            // counterpart (pending) cannot be pushed, so skip and report them.
            if ($entityType === '' || $magentoId === '') {
                $skipped++;
                continue;
            }

            $done = $this->queue->enqueue([
                'entity_type' => $entityType,
                'magento_id' => $magentoId,
                'website_id' => (int)$map->getData('website_id'),
                'vendor_id' => (int)$map->getData('vendor_id'),
                'direction' => 'm2o',
                'operation' => 'update',
                'correlation_id' => $this->correlationId->generate(),
            ]);

            $done ? $enqueued++ : $skipped++;
        }

        if ($enqueued > 0) {
            $this->messageManager->addSuccessMessage(
                (string)__('%1 mapping(s) re-enqueued for sync to Odoo; the cron consumer will push them.', $enqueued)
            );
        }
        if ($skipped > 0) {
            $this->messageManager->addNoticeMessage(
                (string)__('%1 row(s) skipped (no Magento id, or an identical sync is already pending).', $skipped)
            );
        }
        if ($enqueued === 0 && $skipped === 0) {
            $this->messageManager->addWarningMessage((string)__('No mappings were selected.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
