<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Setup\Patch\Data;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Model\CustomField\CustomerForm;
use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateCustomFields implements DataPatchInterface
{
    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * @var CustomerForm
     */
    private $customForm;

    public function __construct(
        CustomerForm $customForm,
        EavSetup $eavSetup
    ) {
        $this->eavSetup = $eavSetup;
        $this->customForm = $customForm;
    }
    public function apply(): UpdateCustomFields
    {
        $dataForm = [];

        for ($i = 1; $i <= CustomFieldsConfigInterface::COUNT_OF_CUSTOM_FIELDS; $i++) {
            $attributeData = $this->eavSetup->getAttribute(AttributeProvider::ENTITY, 'custom_field_' . $i);
            if ($attributeData) {
                $this->eavSetup->updateAttribute(
                    AttributeProvider::ENTITY,
                    'custom_field_' . $i,
                    'backend_type',
                    'varchar'
                );
                $dataForm[] = [
                    'form_code' => 'customer_address_edit',
                    'attribute_id' => $attributeData['attribute_id']
                ];
            }
        }

        if ($dataForm) {
            $this->customForm->addFormForAttribute($dataForm);
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
