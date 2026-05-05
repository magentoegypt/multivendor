<?php
namespace Mstore\OrdersList\Api;

/**
 * @api
 */
interface OrdersListInterface
{
    /**
     * Orders list
     *
     * @param int $page
     * @return string success
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    public function getAllOrders($page);
}