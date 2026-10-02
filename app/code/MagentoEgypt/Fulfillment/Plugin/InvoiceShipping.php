<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

final class InvoiceShipping
{
    public function aroundCollect($subject, callable $proceed, $invoice)
    {
        if (!$invoice->getOrder()->getData('hf_plan_json')) return $proceed($invoice);
        // Do not lose marketplace-owned shipping when Vnecoms sums vendor invoice shipping only.
        return (new \Magento\Sales\Model\Order\Invoice\Total\Shipping())->collect($invoice);
    }
}
