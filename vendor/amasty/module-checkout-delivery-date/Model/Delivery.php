<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Model;

use Amasty\CheckoutDeliveryDate\Api\Data\DeliveryInterface;
use Magento\Framework\Model\AbstractModel;

class Delivery extends AbstractModel implements DeliveryInterface
{
    protected function _construct()
    {
        $this->_init(ResourceModel\Delivery::class);
    }

    /**
     * @return string|int|null
     */
    public function getId()
    {
        return $this->_getData(self::ENTITY_ID);
    }

    /**
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        return $this->_getData(self::ORDER_ID);
    }

    /**
     * @param int $id
     */
    public function setOrderId(int $id): void
    {
        $this->setData(self::ORDER_ID, $id);
    }

    /**
     * @return int|null
     */
    public function getQuoteId(): ?int
    {
        return $this->_getData(self::QUOTE_ID);
    }

    /**
     * @param int $id
     */
    public function setQuoteId(int $id): void
    {
        $this->setData(self::QUOTE_ID, $id);
    }

    /**
     * @return string|int|null
     */
    public function getDate()
    {
        return $this->_getData(self::DATE);
    }

    /**
     * @param string|int|null $date
     */
    public function setDate($date): void
    {
        $this->setData(self::DATE, $date);
    }

    /**
     * @return string|int|null
     */
    public function getTime()
    {
        return $this->_getData(self::TIME);
    }

    /**
     * @param string|int|null $time
     */
    public function setTime($time): void
    {
        $this->setData(self::TIME, $time);
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->_getData(self::COMMENT);
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment): void
    {
        $this->setData(self::COMMENT, $comment);
    }
}
