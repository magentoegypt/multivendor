<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\Quotation\Setup\Patch\Schema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
 * Class UpdateBundleRelatedSchema
 *
 * @package Magento\Bundle\Setup\Patch
 */
class RemoveForeignKeys implements SchemaPatchInterface, PatchVersionInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * UpdateBundleRelatedSchema constructor.
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        // Updating data of the 'catalog_product_bundle_option_value' table.
        $tableName = $this->schemaSetup->getTable('vnecoms_quotation_quote');

        $existingForeignKeys = $this->schemaSetup->getConnection()->getForeignKeys($tableName);
        foreach ($existingForeignKeys as $key) {
            if ($key['FK_NAME'] != "VNECOMS_QUOTATION_QUOTE_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID") continue;
            $this->schemaSetup->getConnection()->dropForeignKey($key['TABLE_NAME'], $key['FK_NAME']);
        }
        
        $existingForeignKeys = $this->schemaSetup->getConnection()->getIndexList($tableName);
        foreach ($existingForeignKeys as $key) {
            if ($key['KEY_NAME'] != "VNECOMS_QUOTATION_QUOTE_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID") continue;
            $this->schemaSetup->getConnection()->dropIndex($key['TABLE_NAME'], $key['KEY_NAME']);
        }

        $this->schemaSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
