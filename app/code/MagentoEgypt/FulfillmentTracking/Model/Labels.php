<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\FulfillmentTracking\Model;

/**
 * Display text for the delivery codes, through __() so the store view's language applies
 * (i18n/ar_SA.csv). The app and the website read the same strings from here, so a status never
 * reads one way on the phone and another on the order page.
 *
 * Callers outside a storefront request (GraphQL) wrap the call in HubApp's storefront emulation.
 */
class Labels
{
    public function status(string $code): string
    {
        return (string) match ($code) {
            StatusMap::ORDERED => __('Order placed'),
            StatusMap::PREPARING => __('Preparing'),
            StatusMap::READY => __('Ready for courier pickup'),
            StatusMap::TO_HUB => __('On the way to our warehouse'),
            StatusMap::AT_HUB => __('At our warehouse'),
            StatusMap::SHIPPED => __('Shipped'),
            StatusMap::ON_THE_WAY => __('On the way to you'),
            StatusMap::DELIVERED => __('Delivered'),
            StatusMap::COMPLETED => __('Completed'),
            StatusMap::DELAYED => __('Delivery delayed'),
            StatusMap::ON_HOLD => __('On hold'),
            StatusMap::CANCELED => __('Canceled'),
            StatusMap::REFUNDED => __('Refunded'),
            default => __('Preparing'),
        };
    }

    public function route(string $code): string
    {
        return (string) match ($code) {
            'to_hub' => __('From the seller to our warehouse'),
            'from_hub' => __('From our warehouse to you'),
            default => __('From the seller to you'),
        };
    }

    public function courier(string $type, ?string $handledBy): string
    {
        if ($type === 'third_party') {
            return (string) __('Courier');
        }
        return (string) ($handledBy === 'seller' ? __("Seller's own delivery") : __('Hub Market delivery'));
    }

    /**
     * Fills every *_label key from its *_code / type sibling, in place.
     *
     * @param array<string, mixed> $fulfillment one order's projection
     * @return array<string, mixed>
     */
    public function apply(array $fulfillment): array
    {
        $fulfillment['status_label'] = $this->status($fulfillment['status_code']);
        foreach ($fulfillment['deliveries'] as &$delivery) {
            $delivery['status_label'] = $this->status($delivery['status_code']);
            $delivery['route_label'] = $this->route($delivery['route']);
            if ($delivery['courier'] !== null) {
                $delivery['courier']['label'] = $this->courier($delivery['courier']['type'], $delivery['handled_by']);
            }
            foreach ($delivery['timeline'] as &$step) {
                $step['status_label'] = $this->status($step['status_code']);
            }
            unset($step);
        }
        unset($delivery);

        return $fulfillment;
    }
}
