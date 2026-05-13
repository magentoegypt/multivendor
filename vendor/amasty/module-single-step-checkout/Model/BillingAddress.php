<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model;

use Amasty\CheckoutCore\Block\Onepage\LayoutWalker;
use Amasty\CheckoutStyleSwitcher\Model\ConfigProvider as StyleSwitcherConfig;

class BillingAddress
{
    /**
     * @var StyleSwitcherConfig
     */
    private $styleSwitcherConfig;

    public function __construct(
        StyleSwitcherConfig $styleSwitcherConfig
    ) {
        $this->styleSwitcherConfig = $styleSwitcherConfig;
    }

    public function getBillingPath(LayoutWalker $walker): array
    {
        $billingAddressPath = [];
        $billingAddressDisplayOn = $this->styleSwitcherConfig->getBillingAddressDisplayOn();

        switch ($billingAddressDisplayOn) {
            case StyleSwitcherConfig::BILLING_ADDRESS_ON_PAYMENT_METHOD:
                $billingRoot = $walker->getValue('{PAYMENT}.>>.payments-list.>>');
                foreach ($billingRoot as $key => $item) {
                    if (isset($item['children']['form-fields'])) {
                        $billingAddressPath[] = '{PAYMENT}.>>.payments-list.>>.' . $key . '.>>.form-fields.>>';
                    }
                }
                break;
                
            case StyleSwitcherConfig::BILLING_ADDRESS_ON_PAYMENT_PAGE:
                $billingAddressPath[] = '{PAYMENT}.>>.afterMethods.>>.billing-address-form.>>.form-fields.>>';
                break;

            case StyleSwitcherConfig::BILLING_ADDRESS_BELOW_SHIPPING_ADDRESS:
                $billingAddressPath[] = '{SHIPPING_ADDRESS}.>>.billing-address-form.>>.form-fields.>>';
                break;
        }

        return $billingAddressPath;
    }
}
