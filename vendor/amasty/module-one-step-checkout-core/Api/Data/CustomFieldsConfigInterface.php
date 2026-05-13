<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api\Data;

interface CustomFieldsConfigInterface
{
    /**
     * Constants defined for config values
     */
    public const COUNT_OF_CUSTOM_FIELDS = 3;
    public const CUSTOM_FIELD_INDEX = 1;
    public const CUSTOM_FIELD_1_CODE = 'custom_field_1';
    public const CUSTOM_FIELD_2_CODE = 'custom_field_2';
    public const CUSTOM_FIELD_3_CODE = 'custom_field_3';
    public const CUSTOM_FIELDS_ARRAY = [
        self::CUSTOM_FIELD_1_CODE,
        self::CUSTOM_FIELD_2_CODE,
        self::CUSTOM_FIELD_3_CODE
    ];
}
