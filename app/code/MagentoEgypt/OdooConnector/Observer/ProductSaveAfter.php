<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Queue\QueueManager;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;

/**
 * Enqueues a Magento->Odoo product sync on save. It only enqueues (never calls
 * Odoo inline) so the save stays fast and the work is retryable.
 */
class ProductSaveAfter implements ObserverInterface
{
    private Config $config;
    private QueueManager $queue;
    private CorrelationId $correlationId;

    public function __construct(
        Config $config,
        QueueManager $queue,
        CorrelationId $correlationId
    ) {
        $this->config = $config;
        $this->queue = $queue;
        $this->correlationId = $correlationId;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isDomainEnabled('products')) {
            return;
        }

        $product = $observer->getEvent()->getData('product');
        if (!$product || !$product->getId()) {
            return;
        }

        $this->queue->enqueue([
            'entity_type' => 'product',
            'magento_id' => (string)$product->getId(),
            'website_id' => 0,
            'direction' => 'm2o',
            'operation' => 'update',
            'correlation_id' => $this->correlationId->generate(),
        ]);
    }
}
