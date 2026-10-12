<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\FulfillmentTracking\Block\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use MagentoEgypt\FulfillmentTracking\Model\DeliveryProjection;
use MagentoEgypt\FulfillmentTracking\Model\Labels;

/**
 * "Delivery progress" on the website's order page (customer and guest views), from the same
 * projection and labels the app's CustomerOrder.hm_fulfillment uses, so the two always agree.
 *
 * The order is the one Magento's own order view already loaded and authorised (current_order);
 * nothing is read by an id from the request.
 */
class Delivery extends Template
{
    private ?array $fulfillment = null;
    private bool $loaded = false;

    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        private readonly DeliveryProjection $projection,
        private readonly Labels $labels,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?Order
    {
        $order = $this->registry->registry('current_order');
        return $order instanceof Order && $order->getId() ? $order : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFulfillment(): ?array
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $order = $this->getOrder();
            if ($order !== null) {
                $storeId = (int) $this->_storeManager->getStore()->getId();
                $result = $this->projection->forOrders([$order], $storeId)[(int) $order->getId()] ?? null;
                $this->fulfillment = $result !== null ? $this->labels->apply($result) : null;
            }
        }
        return $this->fulfillment;
    }

    /**
     * Names of the lines a delivery carries, in order-item order.
     *
     * @param string[] $itemUids
     * @return string[]
     */
    public function getItemNames(array $itemUids): array
    {
        $order = $this->getOrder();
        $names = [];
        foreach ($itemUids as $uid) {
            $item = $order?->getItemById((int) base64_decode($uid, true));
            if ($item !== null) {
                $qty = (float) $item->getQtyOrdered();
                $names[] = $item->getName() . ($qty > 1 ? ' × ' . (string) (0 + $qty) : '');
            }
        }
        return $names;
    }

    public function formatWhen(?string $iso, bool $withTime = true): string
    {
        if ($iso === null || $iso === '') {
            return '';
        }
        return (string) $this->formatDate($iso, \IntlDateFormatter::MEDIUM, $withTime);
    }

    protected function _toHtml()
    {
        $fulfillment = $this->getFulfillment();
        return $fulfillment !== null && $fulfillment['deliveries'] !== [] ? parent::_toHtml() : '';
    }
}
