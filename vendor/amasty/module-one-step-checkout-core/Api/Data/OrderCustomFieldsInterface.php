<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api\Data;

interface OrderCustomFieldsInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const NAME = 'name';
    public const BILLING_VALUE = 'billing_value';
    public const SHIPPING_VALUE = 'shipping_value';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return int|null
     */
    public function getOrderId();

    /**
     * @param int $id
     *
     * @return \Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface
     */
    public function setOrderId($id);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return \Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getBillingValue();

    /**
     * @param string $value
     *
     * @return \Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface
     */
    public function setBillingValue($value);

    /**
     * @return string|null
     */
    public function getShippingValue();

    /**
     * @param string $value
     *
     * @return \Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface
     */
    public function setShippingValue($value);
}
