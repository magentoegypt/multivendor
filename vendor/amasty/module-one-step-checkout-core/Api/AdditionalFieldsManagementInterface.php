<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api;

interface AdditionalFieldsManagementInterface
{
    /**
     * @param int $cartId
     * @param \Amasty\CheckoutCore\Api\Data\AdditionalFieldsInterface $fields
     *
     * @return bool
     */
    public function save($cartId, $fields);

    /**
     * @param int $quoteId
     *
     * @return \Amasty\CheckoutCore\Api\Data\AdditionalFieldsInterface|\Amasty\CheckoutCore\Model\AdditionalFields
     */
    public function getByQuoteId($quoteId);
}
