<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;
class Carrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    protected $_code='hubfulfillment';
    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request) { return false; }
    public function getAllowedMethods() { return ['direct'=>__('Direct delivery'),'hub'=>__('Consolidated hub delivery')]; }
}
