<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel;

class AttributeFormCodes
{
    public const ADMINHTML_CHECKOUT = 'adminhtml_checkout';
    public const ADMINHTML_CUSTOMER = 'adminhtml_customer';
    public const CUSTOMER_ACCOUNT_CREATE = 'customer_account_create';
    public const CUSTOMER_ACCOUNT_EDIT = 'customer_account_edit';

    // Amasty_CustomerAttributes
    public const AMASTY_CUSTOM_ATTRIBUTES = 'amasty_custom_attribute';
    public const AMASTY_CUSTOM_ATTRIBUTES_REGISTRATION = 'customer_attributes_registration';
    public const AMASTY_CUSTOM_ATTRIBUTES_CHECKOUT = 'customer_attributes_checkout';
}
