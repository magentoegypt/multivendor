<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\Source\Gdpr;

use Magento\Framework\Data\OptionSourceInterface;

class CheckboxLocation implements OptionSourceInterface
{
    public const SHIPPING_ADDRESS = 'shipping_address';
    public const SHIPPING_METHOD = 'shipping_method';
    public const DELIVERY = 'delivery';
    public const PAYMENT_METHOD = 'payment_method';
    public const SUMMARY = 'summary';

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [
            [
                'label' => __('Below the Shipping Address'),
                'value' => self::SHIPPING_ADDRESS
            ],
            [
                'label' => __('Below the Shipping Method'),
                'value' => self::SHIPPING_METHOD
            ],
            [
                'label' => __('Below Delivery Date/Time/Comment'),
                'value' => self::DELIVERY
            ],
            [
                'label' => __('Below the Payment Method'),
                'value' => self::PAYMENT_METHOD
            ],
            [
                'label' => __('Below the Order Total'),
                'value' => self::SUMMARY
            ]
        ];

        return [
            [
                'label' => __('Amasty One Step Checkout'),
                'value' => $options
            ]
        ];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::SHIPPING_ADDRESS,
            self::SHIPPING_METHOD,
            self::DELIVERY,
            self::PAYMENT_METHOD,
            self::SUMMARY
        ];
    }
}
