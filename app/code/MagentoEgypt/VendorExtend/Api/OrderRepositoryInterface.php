<?php

namespace MagentoEgypt\VendorExtend\Api;

/**
 * Vendor CRUD interface.
 * @api
 */
interface OrderRepositoryInterface
{
    /**
     * @param int $customerId
     * @param int $orderId
     * @return \MagentoEgypt\VendorExtend\Api\Data\Sale\OrderInterface
     */
    public function getOrder($customerId, $orderId);
}
