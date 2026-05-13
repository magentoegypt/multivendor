<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\FieldFactory;

class DuplicateField
{
    /**
     * @var FieldFactory
     */
    private $fieldFactory;

    public function __construct(FieldFactory $fieldFactory)
    {
        $this->fieldFactory = $fieldFactory;
    }

    public function execute(Field $field): Field
    {
        $data = $field->getData();
        unset($data[Field::ID]);

        $duplicatedField = $this->fieldFactory->create();
        $duplicatedField->setData($data);
        return $duplicatedField;
    }
}
