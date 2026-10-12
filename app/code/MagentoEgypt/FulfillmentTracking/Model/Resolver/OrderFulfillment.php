<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\FulfillmentTracking\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MagentoEgypt\FulfillmentTracking\Model\DeliveryProjection;
use MagentoEgypt\FulfillmentTracking\Model\Labels;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use Psr\Log\LoggerInterface;

/**
 * CustomerOrder.hm_fulfillment — delivery progress for every order of a response in one batch.
 *
 * Access is the core query's, as for hm_packages: the parent's "model" is the order SalesGraphQl
 * already authorised (customer.orders for the token's customer, guestOrder / guestOrderByToken
 * for the guest's details or token). A parent without one gets null; no order is loaded by id here.
 * No @cache: the core order queries are per customer and uncached.
 *
 * Labels are rendered inside a storefront emulation of the request's store, so an Arabic store
 * answers in Arabic. A failure is logged and answered with null for that order only.
 */
class OrderFulfillment implements BatchResolverInterface
{
    public function __construct(
        private readonly DeliveryProjection $projection,
        private readonly Labels $labels,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $orders = [];
        foreach ($requests as $key => $request) {
            $model = ((array) ($request->getValue() ?? []))['model'] ?? null;
            if ($model instanceof OrderInterface) {
                $orders[$key] = $model;
            }
        }

        $byOrder = [];
        if ($orders) {
            $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
            try {
                $byOrder = $this->emulation->run($storeId, function () use ($orders, $storeId): array {
                    $result = $this->projection->forOrders(array_values($orders), $storeId);
                    foreach ($result as $orderId => $fulfillment) {
                        $result[$orderId] = $this->labels->apply($fulfillment);
                    }
                    return $result;
                });
            } catch (\Throwable $e) {
                $this->logger->error('FulfillmentTracking: hm_fulfillment failed: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        $response = new BatchResponse();
        foreach ($requests as $key => $request) {
            $order = $orders[$key] ?? null;
            $response->addResponse($request, $order !== null ? ($byOrder[(int) $order->getEntityId()] ?? null) : null);
        }

        return $response;
    }
}
