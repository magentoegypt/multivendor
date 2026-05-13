<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Setup\Patch\Data;

use Amasty\CheckoutCore\Model\Customer\Address\Attribute\GetRestrictedCodes;
use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RestoreRestrictedAttributes implements DataPatchInterface
{
    /**
     * @var GetRestrictedCodes
     */
    private $getRestrictedCodes;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AdapterInterface
     */
    private $connection;

    public function __construct(
        GetRestrictedCodes $getRestrictedCodes,
        ResourceConnection $resourceConnection
    ) {
        $this->getRestrictedCodes = $getRestrictedCodes;
        $this->resourceConnection = $resourceConnection;
        $this->connection = $resourceConnection->getConnection();
    }

    public function apply()
    {
        $attributeIds = $this->getAttributeIds($this->getRestrictedCodes->execute());
        $this->updateAttributes($attributeIds);
        $this->updateFields($attributeIds);

        return $this;
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [AddAttributesToManageCheckoutFields::class];
    }

    private function updateAttributes(array $attributeIds): void
    {
        $whereExpr = $this->connection->quoteInto(AttributeInterface::ATTRIBUTE_ID . ' in (?)', $attributeIds);

        $this->connection->update(
            $this->resourceConnection->getTableName('eav_attribute'),
            [AttributeInterface::IS_REQUIRED => 0],
            $whereExpr
        );

        $this->connection->update(
            $this->resourceConnection->getTableName('customer_eav_attribute_website'),
            ['is_required' => 0],
            $whereExpr
        );
    }

    private function updateFields(array $attributeIds): void
    {
        $this->connection->update(
            $this->resourceConnection->getTableName(FieldResource::MAIN_TABLE),
            [Field::REQUIRED => 0],
            $this->connection->quoteInto(Field::ATTRIBUTE_ID . ' in (?)', $attributeIds)
        );
    }

    private function getAttributeIds(array $attributeCodes): array
    {
        $select = $this->connection->select()
            ->from(
                ['attr' => $this->resourceConnection->getTableName('eav_attribute')],
                [AttributeInterface::ATTRIBUTE_ID]
            )
            ->joinInner(
                ['type' => $this->resourceConnection->getTableName('eav_entity_type')],
                $this->connection->quoteInto(
                    'attr.entity_type_id = type.entity_type_id and type.entity_type_code = ?',
                    AddressMetadataInterface::ENTITY_TYPE_ADDRESS
                ),
                []
            )
            ->where(AttributeInterface::ATTRIBUTE_CODE . ' in (?)', $attributeCodes);

        return $this->connection->fetchCol($select);
    }
}
