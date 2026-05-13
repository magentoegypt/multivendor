<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Cache\CachedLayoutProcessor\AddressFormAttributes;

/**
 * Update attribute default values
 */
class DefaultAttributeValueUpdate extends \Magento\Checkout\Block\Checkout\AttributeMerger
{
    /**
     * Update attributes
     * @param array $layoutJsAttributes
     *
     * @return array
     */
    public function updateDefaultValuesOfLayoutJs(array $layoutJsAttributes): array
    {
        foreach ($layoutJsAttributes as $attributeCode => &$element) {
            if ($attributeCode !== 'country_id') {
                $defaultValue = $this->getDefaultValue($attributeCode);
                if (null !== $defaultValue) {
                    $element['value'] = $defaultValue;
                }
            }
        }

        return $layoutJsAttributes;
    }
}
