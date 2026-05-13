<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Api\Data;

interface DeliveryInterface
{
    public const ENTITY_ID = 'id';
    public const ORDER_ID = 'order_id';
    public const QUOTE_ID = 'quote_id';
    public const DATE = 'date';
    public const TIME = 'time';
    public const COMMENT = 'comment';

    /**
     * @return string|int|null
     */
    public function getId();

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int $id
     * @return void
     */
    public function setOrderId(int $id): void;

    /**
     * @return int|null
     */
    public function getQuoteId(): ?int;

    /**
     * @param int $id
     * @return void
     */
    public function setQuoteId(int $id): void;

    /**
     * @return string|int|null
     */
    public function getDate();

    /**
     * @param string|int|null $date
     * @return void
     */
    public function setDate($date): void;

    /**
     * @return string|int|null
     */
    public function getTime();

    /**
     * @param string|int|null $time
     * @return void
     */
    public function setTime($time): void;

    /**
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * @param string $comment
     * @return void
     */
    public function setComment(string $comment): void;
}
