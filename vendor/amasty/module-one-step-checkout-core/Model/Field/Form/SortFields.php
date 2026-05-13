<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form;

use Amasty\CheckoutCore\Model\Field;

class SortFields
{
    /**
     * @param Field[] $fields
     * @return void
     * @see \Amasty\CheckoutCore\Model\LayoutProcessor\SortFields
     */
    public function execute(array &$fields): void
    {
        uksort($fields, static function (string $firstKey, string $secondKey) use ($fields) {
            $firstField = $fields[$firstKey];
            $secondField = $fields[$secondKey];

            $diff = $firstField->getSortOrder() <=> $secondField->getSortOrder();
            return $diff !== 0 ? $diff : strcmp($firstKey, $secondKey);
        });
    }
}
