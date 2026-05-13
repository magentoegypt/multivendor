<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form\Processor\FieldsByStore;

use Amasty\CheckoutCore\Model\Field;

class CanUseDefaultField
{
    public const USE_DEFAULT = 'use_default';

    public function execute(?Field $field, Field $defaultField, array $fieldData): bool
    {
        $targetField = $field ?? $defaultField;

        return isset($fieldData[self::USE_DEFAULT])
            && (int) $targetField->getData(Field::ENABLED) === (int) $fieldData[Field::ENABLED]
            && $targetField->getSortOrder() === (int) $fieldData[Field::SORT_ORDER];
    }
}
