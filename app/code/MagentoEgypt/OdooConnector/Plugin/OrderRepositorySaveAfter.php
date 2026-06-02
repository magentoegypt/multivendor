<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Queue\QueueManager;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;

/**
 * Enqueues a Magento->Odoo order sync whenever an order is saved via the
 * repository (the modern path used by checkout, admin and the API — the legacy
 * sales_order_save_after model event does NOT fire here). Create-once in Odoo,
 * so repeated saves are cheap.
 */
class OrderRepositorySaveAfter
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

    public function afterSave(OrderRepositoryInterface $subject, OrderInterface $result): OrderInterface
    {
        if ($this->config->isDomainEnabled('orders') && $result->getEntityId()) {
            $this->queue->enqueue([
                'entity_type' => 'order',
                'magento_id' => (string)$result->getEntityId(),
                'website_id' => 0,
                'direction' => 'm2o',
                'operation' => 'update',
                'correlation_id' => $this->correlationId->generate(),
            ]);
        }

        return $result;
    }
}
