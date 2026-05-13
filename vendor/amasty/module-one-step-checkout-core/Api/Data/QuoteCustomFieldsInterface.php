<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api\Data;

interface QuoteCustomFieldsInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
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
    public function getQuoteId();

    /**
     * @param int $id
     *
     * @return \Amasty\CheckoutCore\Api\Data\QuoteCustomFieldsInterface
     */
    public function setQuoteId($id);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return \Amasty\CheckoutCore\Api\Data\QuoteCustomFieldsInterface
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getBillingValue();

    /**
     * @param string $value
     *
     * @return \Amasty\CheckoutCore\Api\Data\QuoteCustomFieldsInterface
     */
    public function setBillingValue($value);

    /**
     * @return string|null
     */
    public function getShippingValue();

    /**
     * @param string $value
     *
     * @return \Amasty\CheckoutCore\Api\Data\QuoteCustomFieldsInterface
     */
    public function setShippingValue($value);
}
