<?php
namespace MagentoEgypt\Fulfillment\Api;
interface CustomerInterface
{
    /**
     * @param int $orderId
     * @return string
     */
    public function order($orderId);
    /**
     * @param string $number
     * @return string
     */
    public function byNumber($number);
}
