<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement;

use Amasty\CheckoutCore\Model\Field\GetDefaultField;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Magento\Framework\Exception\AlreadyExistsException;

class UpdateDefaultField
{
    /**
     * @var GetDefaultField
     */
    private $getDefaultField;

    /**
     * @var FieldResource
     */
    private $fieldResource;

    public function __construct(
        GetDefaultField $getDefaultField,
        FieldResource $fieldResource
    ) {
        $this->getDefaultField = $getDefaultField;
        $this->fieldResource = $fieldResource;
    }

    /**
     * @param int $attributeId
     * @param bool $isEnabled
     * @param bool $isRequired
     * @throws AlreadyExistsException
     * @return void
     */
    public function execute(int $attributeId, bool $isEnabled, bool $isRequired): void
    {
        $field = $this->getDefaultField->execute($attributeId);

        if (!$field) {
            return;
        }

        $field->setIsEnabled($isEnabled);
        $field->setIsRequired($isRequired);
        $this->fieldResource->save($field);
    }
}
