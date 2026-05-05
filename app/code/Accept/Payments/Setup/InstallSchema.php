<?php
namespace Accept\Payments\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface {

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
        $table = $setup->getConnection()->newTable($setup->getTable('Accept_Payments_Tokens'));

        $table->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array('identity' => true,'nullable' => false,'primary' => true,'unsigned' => true,),
            'ID'
        );

        $table->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
            20,
            ['nullable'=>false],
            'Customer ID'
        );

        $table->addColumn(
            'card_subtype',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            56,
            ['nullable'=>false,'default'=>''],
            'Card Type'
        );

        $table->addColumn(
            'token',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            56,
            ['nullable'=>false,'default'=>''],
            'Payment Token'
        );

        $table->addColumn(
            'masked_pan',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            19,
            ['nullable'=>false,'default'=>''],
            'Pan'
        );
        $setup->getConnection()->createTable($table);
        $setup->endSetup();
    }
}