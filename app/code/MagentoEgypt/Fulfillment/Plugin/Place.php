<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

final class Place
{
    public function __construct(private \MagentoEgypt\Fulfillment\Model\Configuration $config) {}
    public function beforePlace($subject, $order): array
    {
        if ($this->config->checkoutEnabled() && !$order->getIsVirtual() && !$order->getData('hf_validated_checkout')) {
            throw new \Magento\Framework\Exception\LocalizedException(__('This order must pass Hub Market delivery allocation before placement.'));
        }
        return [$order];
    }
}
