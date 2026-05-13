<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */
namespace Amasty\CheckoutCore\Api\Data;

interface FeeInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const ENTITY_ID = 'id';
    public const ORDER_ID = 'order_id';
    public const QUOTE_ID = 'quote_id';
    public const AMOUNT = 'amount';
    public const BASE_AMOUNT = 'base_amount';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return int|null
     */
    public function getOrderId();

    /**
     * @return int|null
     */
    public function getQuoteId();

    /**
     * @return int
     */
    public function getAmount();

    /**
     * @return int
     */
    public function getBaseAmount();

    /**
     * @param int $id
     * @return \Amasty\CheckoutCore\Api\Data\FeeInterface
     */
    public function setOrderId($id);

    /**
     * @param int $id
     * @return \Amasty\CheckoutCore\Api\Data\FeeInterface
     */
    public function setQuoteId($id);

    /**
     * @param int $amount
     * @return \Amasty\CheckoutCore\Api\Data\FeeInterface
     */
    public function setAmount($amount);

    /**
     * @param int $amount
     * @return \Amasty\CheckoutCore\Api\Data\FeeInterface
     */
    public function setBaseAmount($amount);
}
