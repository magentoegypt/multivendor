<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

final class ShippingTax
{
    public function afterGetShippingDataObject($subject,$result,$assignment,$total,$useBaseCurrency)
    {
        $method=(string)$assignment->getShipping()->getMethod();
        if ($result && in_array($method,['hubfulfillment_direct','hubfulfillment_hub'],true)) {
            // Policy rates are always net. Magento still determines jurisdiction, tax class and discounts.
            $result->setIsTaxIncluded(false);
        }
        return $result;
    }
}
