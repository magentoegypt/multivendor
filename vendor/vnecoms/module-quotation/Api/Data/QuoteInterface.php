<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Api\Data;

interface QuoteInterface
{
    const QUOTE_ID = 'entity_id';
    const INCREMENT_ID = 'increment_id';
    const FIRSTNAME = 'customer_firstname';
    const LASTNAME = 'customer_lastname';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const STORE_ID = 'store_id';
    const CUSTOMER_ID = 'customer_id';
    const EMAIL = 'customer_email';
    const COMPANY = 'customer_company';
    const IP_ADDR = 'ip_address';
    const TAX_VAT = 'customer_taxvat';
    const CLIENT_COMMENT = 'client_comment';
    const TELEPHONE = 'customer_telephone';
    const NOTE = 'note';
    const STATUS_BEFORE_HOLD = 'status_before_hold';
    const EXPIRED_DATE = 'expired_date';
    const REMINDER_DATE = 'reminder_date';
    const STATUS = 'status';
    const ITEMS = 'items';


    /**
     * Get Quote ID
     * @return string|null
     */

    public function getQuoteId();

    /**
     * Set quote_id
     * @param string $quoteId
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */

    public function setQuoteId($quoteId);

    /**
     * Get increment_id
     * @return string|null
     */

    public function getIncrementId();

    /**
     * Set increment_id
     * @param string $increment_id
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */

    public function setIncrementId($increment_id);

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer();

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function setCustomer(\Magento\Customer\Api\Data\CustomerInterface $customer);

    /**
     * @return string
     */
    public function getFirstName();

    /**
     * @return string
     */
    public function getLastName();

    /**
     * Get Tax VAT
     * @return string
     */
    public function getTaxVat();

    /**
     * Get Store ID
     * @return integer
     */
    public function getStoreId();


    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore();

    /**
     * @return string
     */
    public function getCustomerName();

    /**
     * @return bool|\Vnecoms\Quotation\Api\Data\ItemInterface[]|bool|\Vnecoms\Quotation\Api\Data\QuoteInterface[]
     */
    public function getItemsCollection();

    /**
     * @return bool|\Vnecoms\Quotation\Api\Data\MessageInterface[]
     */
    public function getMessagesCollection();

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param null|float|\Magento\Framework\DataObject $params
     * @return QuoteInterface
     */
    public function addProduct(\Magento\Catalog\Model\Product $product, $params);

    /**
     * @return QuoteInterface
     */
    public function cancel();

    /**
     * @return QuoteInterface
     */
    public function submit();

    /**
     * @return QuoteInterface
     */
    public function hold();

    /**
     * @return QuoteInterface
     */
    public function unHold();

    /**
     * @return QuoteInterface
     */
    public function reject();

    /**
     * @return QuoteInterface
     */
    public function accept();

    /**
     * @return QuoteInterface
     */
    public function delete();

    /**
     * @return bool
     */
    public function canUnhold();

    /**
     * @return bool
     */
    public function canHold();
    
    /**
     * @return bool
     */
    public function canApprove();
    
    /**
     * @return bool
     */
    public function canCancel();
}
