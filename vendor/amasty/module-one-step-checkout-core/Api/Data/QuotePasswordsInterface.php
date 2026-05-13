<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api\Data;

interface QuotePasswordsInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const ENTITY_ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const PASSWORD_HASH = 'password_hash';

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     *
     * @return \Amasty\CheckoutCore\Api\Data\QuotePasswordsInterface
     */
    public function setEntityId($entityId);

    /**
     * @return int
     */
    public function getQuoteId();

    /**
     * @param int $quoteId
     *
     * @return \Amasty\CheckoutCore\Api\Data\QuotePasswordsInterface
     */
    public function setQuoteId($quoteId);

    /**
     * @return string|null
     */
    public function getPasswordHash();

    /**
     * @param string|null $passwordHash
     *
     * @return \Amasty\CheckoutCore\Api\Data\QuotePasswordsInterface
     */
    public function setPasswordHash($passwordHash);
}
