<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Plugin;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Queue\QueueManager;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;

/**
 * Enqueues a Magento->Odoo inventory sync whenever MSI source items are saved
 * (the canonical MSI write path). Only enqueues; the consumer pushes on-hand.
 */
class SourceItemsSaveAfter
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

    /**
     * @param SourceItemInterface[] $sourceItems
     * @return mixed
     */
    public function afterExecute(SourceItemsSaveInterface $subject, $result, array $sourceItems = [])
    {
        if (!$this->config->isDomainEnabled('inventory')) {
            return $result;
        }

        foreach ($sourceItems as $item) {
            if (!$item instanceof SourceItemInterface) {
                continue;
            }
            $this->queue->enqueue([
                'entity_type' => 'inventory_source_item',
                'magento_id' => $item->getSourceCode() . ':' . $item->getSku(),
                'website_id' => 0,
                'direction' => 'm2o',
                'operation' => 'stock',
                'correlation_id' => $this->correlationId->generate(),
            ]);
        }

        return $result;
    }
}
