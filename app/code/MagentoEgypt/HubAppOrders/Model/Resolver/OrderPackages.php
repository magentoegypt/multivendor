<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MagentoEgypt\HubAppOrders\Model\Package\PackageService;
use Psr\Log\LoggerInterface;

/**
 * CustomerOrder.hm_packages — the order split by store, for every order of a response in one batch
 * (a page of customer.orders is one call, not one per order).
 *
 * The parent value is SalesGraphQl's formatted order, whose "model" is the order the core query
 * already authorised: customer.orders for the token's customer, guestOrder / guestOrderByToken for
 * the guest's details or token, placeOrder / cancelOrder for the caller's own order. Nothing is
 * read for a parent without one, and that order gets an empty list. No @cache: the core order
 * queries are per customer and uncached.
 *
 * A failure here is logged and answered with empty lists: the field is non-null, so an error would
 * take the whole order out of a customer's order list, and the app shows an order without packages
 * the way it did before this module.
 */
class OrderPackages implements BatchResolverInterface
{
    public function __construct(
        private readonly PackageService $packages,
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
            try {
                $byOrder = $this->packages->forOrders(
                    array_values($orders),
                    (int) $context->getExtensionAttributes()->getStore()->getId()
                );
            } catch (\Throwable $e) {
                $this->logger->error('HubAppOrders: hm_packages failed: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        $response = new BatchResponse();
        foreach ($requests as $key => $request) {
            $order = $orders[$key] ?? null;
            $response->addResponse(
                $request,
                $order !== null ? ($byOrder[(int) $order->getEntityId()] ?? []) : []
            );
        }

        return $response;
    }
}
