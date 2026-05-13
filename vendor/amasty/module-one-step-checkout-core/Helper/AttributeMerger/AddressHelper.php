<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Helper\AttributeMerger;

class AddressHelper extends \Magento\Customer\Helper\Address
{
    /**
     * Hack for @see \Magento\Checkout\Block\Checkout\AttributeMerger::isFieldVisible
     *
     * With the current implementation, store owners can't set different visibility for the "VAT Number" field using
     * Manage Checkout Fields on store scope than the value set in Store Configuration (Show VAT Number on Storefront).
     * Example: "Show VAT Number on Storefront" is disabled on the website scope but even after enabling this field
     * on Manage Checkout Fields page on store scope, the field still won't appear on the checkout page.
     *
     * This method forces the value to be true to get past that limitation.
     *
     * @return bool
     */
    public function isVatAttributeVisible()
    {
        return true;
    }
}
