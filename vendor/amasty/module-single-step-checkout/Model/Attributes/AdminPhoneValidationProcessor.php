<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model\Attributes;

use Amasty\Checkout\Model\Config as ConfigProvider;
use Amasty\Checkout\Model\Config\Source\PhoneValidationOptions;
use Amasty\CheckoutCore\Model\Config;

class AdminPhoneValidationProcessor
{
    /**
     * @var Config
     */
    private $checkoutConfig;

    public function __construct(
        Config $checkoutConfig
    ) {
        $this->checkoutConfig = $checkoutConfig;
    }

    public function process(string $phoneClass): string
    {
        $validationType = (int)$this->checkoutConfig->getAdditionalOptions(
            ConfigProvider::FIELD_PHONE_VALIDATION_TYPE
        );

        if (($validationType === PhoneValidationOptions::PHONE_VALIDATION_NONE)
            || (!$this->checkoutConfig->isEnabled())
        ) {
            return $phoneClass;
        }

        $validationClasses = [];

        $maxPhoneLength = $this->checkoutConfig->getAdditionalOptions(ConfigProvider::FIELD_PHONE_MAX_LENGTH);
        $minPhoneLength = $this->checkoutConfig->getAdditionalOptions(ConfigProvider::FIELD_PHONE_MIN_LENGTH);

        if ($maxPhoneLength) {
            $validationClasses[] = ' maximum-length-' . $maxPhoneLength;
        }
        if ($minPhoneLength) {
            $validationClasses[] = ' minimum-length-' . $minPhoneLength;
        }

        if ($validationType === PhoneValidationOptions::PHONE_VALIDATION_NUMERIC) {
            $validationClasses[] = ' validate-digits';
        } else {
            $validationClasses[] = ' validate-numbers-and-spec-characters';
        }

        return $phoneClass . implode($validationClasses);
    }
}
