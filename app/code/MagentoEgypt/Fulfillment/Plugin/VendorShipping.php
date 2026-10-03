<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

final class VendorShipping
{
    public function aroundExecute($subject, callable $proceed, $observer)
    {
        if (!$observer->getOrder()->getData('hf_plan_json')) return $proceed($observer);
        // Never reuse an earlier Vnecoms quote rate: the fulfillment journal owns these fees.
        return null;
    }
}
