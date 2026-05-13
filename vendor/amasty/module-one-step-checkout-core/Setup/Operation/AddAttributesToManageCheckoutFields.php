<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Setup\Operation;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Magento\Customer\Helper\Address;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\ResourceModel\Address\Attribute\Collection;
use Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\ResourceConnection;

class AddAttributesToManageCheckoutFields
{
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Field
     */
    private $fieldSingleton;

    /**
     * @var Address
     */
    private $customerAddress;

    /**
     * @var SyncWithCheckoutFields
     */
    private $syncWithCheckoutFields;

    public function __construct(
        Address $customerAddress,
        AttributeCollectionFactory $attributeCollectionFactory,
        ResourceConnection $resourceConnection,
        Field $fieldSingleton,
        SyncWithCheckoutFields $syncWithCheckoutFields
    ) {
        $this->customerAddress = $customerAddress;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->fieldSingleton = $fieldSingleton;
        $this->syncWithCheckoutFields = $syncWithCheckoutFields;
    }

    public function execute(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $fieldTable = $this->resourceConnection->getTableName(FieldResource::MAIN_TABLE);
        $select = $connection->select()->from($fieldTable)->limit(1);
        if ($connection->fetchOne($select) > 0) {
            return;
        }

        /** @var Collection $attributes */
        $attributes = $this->attributeCollectionFactory->create();
        $inheritedAttributes = $this->fieldSingleton->getInheritedAttributes();

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();

            if (isset($inheritedAttributes[$code])) {
                continue;
            }

            if ($code === 'vat_id') {
                $code = 'taxvat';
            }

            $isEnabled = $this->isEnabledForCode($code);

            $bind = [
                'attribute_id' => $attribute->getId(),
                'label'        => $attribute->getDefaultFrontendLabel(),
                'sort_order'   => $attribute->getSortOrder(),
                'required'     => $attribute->getIsRequired(),
                'width'        => 100,
                'enabled'      => $isEnabled
            ];

            $connection->insert($fieldTable, $bind);
        }

        $this->syncWithCheckoutFields->execute();
    }

    private function isEnabledForCode(string $code): bool
    {
        if ($code === 'fax') {
            return false;
        }

        if (in_array($code, ['prefix', 'suffix', 'middlename', 'taxvat'])) {
            return (bool)$this->customerAddress->getConfig($code)
                || $this->isSettingEnabled('customer/address/' . $code . '_show');
        }

        return true;
    }

    private function isSettingEnabled(string $setting): bool
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from(
            $this->resourceConnection->getTableName('core_config_data'),
            'COUNT(*)'
        )->where(
            'path=?',
            $setting
        )->where(
            'value NOT LIKE ?',
            '0'
        );

        return $connection->fetchOne($select) > 0;
    }
}
