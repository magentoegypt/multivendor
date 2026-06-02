<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Queue\QueueManager;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;

/**
 * Enqueues a Magento->Odoo customer sync on save (customer_save_after_data_object
 * fires from CustomerRepository::save for admin, API and registration). Only
 * enqueues; the consumer performs the Odoo write.
 */
class CustomerSaveAfter implements ObserverInterface
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
        if (!$this->config->isDomainEnabled('customers')) {
            return;
        }

        $customer = $observer->getEvent()->getData('customer_data_object');
        if (!$customer || !$customer->getId()) {
            return;
        }

        $this->queue->enqueue([
            'entity_type' => 'customer_buyer',
            'magento_id' => (string)$customer->getId(),
            'website_id' => 0,
            'direction' => 'm2o',
            'operation' => 'update',
            'correlation_id' => $this->correlationId->generate(),
        ]);
    }
}
