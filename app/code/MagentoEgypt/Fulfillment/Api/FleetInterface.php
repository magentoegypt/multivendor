<?php
namespace MagentoEgypt\Fulfillment\Api;
interface FleetInterface
{
    /**
     * @param int $afterId
     * @param int $limit
     * @return string
     */
    public function orders($afterId=0,$limit=50);
    /**
     * @param int $orderId
     * @return string
     */
    public function order($orderId);
    /**
     * @param string $payloadJson
     * @return string
     */
    public function event($payloadJson);
}
