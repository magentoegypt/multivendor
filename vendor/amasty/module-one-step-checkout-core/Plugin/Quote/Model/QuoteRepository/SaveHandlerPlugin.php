<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Quote\Model\QuoteRepository;

use Amasty\CheckoutCore\Model\Quote\CustomFieldItemsProvider;
use Amasty\CheckoutCore\Model\ResourceModel\QuoteCustomFields;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteRepository\SaveHandler;

class SaveHandlerPlugin
{
    /**
     * @var QuoteCustomFields
     */
    private $customFieldsResource;

    /**
     * @var CustomFieldItemsProvider
     */
    private $customFieldItemsProvider;

    public function __construct(
        QuoteCustomFields $customFieldsResource,
        CustomFieldItemsProvider $customFieldItemsProvider
    ) {
        $this->customFieldsResource = $customFieldsResource;
        $this->customFieldItemsProvider = $customFieldItemsProvider;
    }

    /**
     * Save Custom Quote Address Attributes.
     *
     * @param SaveHandler $subject
     * @param CartInterface $quote
     *
     * @return CartInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(SaveHandler $subject, CartInterface $quote): CartInterface
    {
        $customFields = $this->customFieldItemsProvider->getItemsByQuoteId((int)$quote->getId());

        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();

        foreach ($customFields as $field) {
            $customAttribute = $shippingAddress->getCustomAttribute($field->getName());
            if ($customAttribute) {
                $field->setShippingValue($customAttribute->getValue());
            }

            $customAttribute = $billingAddress->getCustomAttribute($field->getName());
            if ($customAttribute) {
                $field->setBillingValue($customAttribute->getValue());
            }

            if ($field->dataHasChangedFor('shipping_value') || $field->dataHasChangedFor('billing_value')) {
                $this->customFieldsResource->save($field);
            }
        }

        return $quote;
    }
}
