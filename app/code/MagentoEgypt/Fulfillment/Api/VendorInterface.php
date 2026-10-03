<?php
namespace MagentoEgypt\Fulfillment\Api;
interface VendorInterface
{
    /** @return string */
    public function workspace();
    /**
     * @param string $policyJson
     * @return bool
     */
    public function savePolicy($policyJson);
    /**
     * @param int $orderId
     * @param string $groupId
     * @param string $state
     * @param string $operationKey
     * @return bool
     */
    public function progress($orderId,$groupId,$state,$operationKey);
    /**
     * @param int $orderId
     * @return string
     */
    public function order($orderId);
    /**
     * @param string $payloadJson
     * @return string
     */
    public function dispatch($payloadJson);
}
