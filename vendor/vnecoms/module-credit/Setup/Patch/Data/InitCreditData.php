<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\Credit\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Vnecoms\Credit\Model\Product\Type\Credit;
use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

/**
 * Class add customer updated attribute to customer
 */
class InitCreditData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * Customer collection factory
     *
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $_customerCollectionFactory;

    /**
     * @var \Vnecoms\Credit\Model\CreditFactory
     */
    private $_creditFactory;

    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    private $_categorySetupFactory;

    /**
     * InitCreditData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CollectionFactory $customerCollectionFactory
     * @param \Vnecoms\Credit\Model\CreditFactory $creditFactory
     * @param \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CollectionFactory $customerCollectionFactory,
        \Vnecoms\Credit\Model\CreditFactory $creditFactory,
        \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_creditFactory = $creditFactory;
        $this->_categorySetupFactory = $categorySetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $categorySetup = $this->_categorySetupFactory->create(
            ['setup' => $this->moduleDataSetup]
        );

        $categorySetup->addAttribute(
            Product::ENTITY,
            'credit_type',
            [
                'group' => 'Product Details',
                'label' => 'Type of Store Credit',
                'type' => 'int',
                'input' => 'select',
                'position' => 4,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => true,
                'user_defined' => false,
                'source' => 'Vnecoms\Credit\Model\Source\Type',
                'default' => '',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_in_product_listing' => true,
                'apply_to'=>Credit::TYPE_CODE,
            ]
        );
        $categorySetup->addAttribute(
            Product::ENTITY,
            'credit_value_fixed',
            [
                'group' => 'Product Details',
                'label' => 'Store Credit Value',
                'type' => 'varchar',
                'input' => 'text',
                'position' => 4,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_in_product_listing' => true,
                'apply_to'=>Credit::TYPE_CODE,
            ]
        );

        $categorySetup->addAttribute(
            Product::ENTITY,
            'credit_value_dropdown',
            [
                'group' => 'Product Details',
                'label' => 'Store Credit Value',
                'type' => 'text',
                'input' => 'text',
                'position' => 4,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'backend' => 'Vnecoms\Credit\Model\Product\Attribute\Backend\CreditDropdown',
                'default' => '',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_in_product_listing' => true,
                'apply_to'=>Credit::TYPE_CODE,
            ]
        );
        $categorySetup->addAttribute(
            Product::ENTITY,
            'credit_value_custom',
            [
                'group' => 'Product Details',
                'label' => 'Store Credit Value',
                'type' => 'varchar',
                'input' => 'text',
                'position' => 4,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'backend' => 'Vnecoms\Credit\Model\Product\Attribute\Backend\CreditCustom',
                'default' => '',
                'note' => 'Enter the range of credit value.',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_in_product_listing' => true,
                'apply_to'=>Credit::TYPE_CODE,
            ]
        );

        $categorySetup->addAttribute(
            Product::ENTITY,
            'credit_price',
            [
                'group' => 'Product Details',
                'label' => 'Credit Package Price',
                'type' => 'decimal',
                'input' => 'price',
                'position' => 4,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_in_product_listing' => true,
                'apply_to'=>Credit::TYPE_CODE,
            ]
        );

        $categorySetup->addAttribute(
            Product::ENTITY,
            'credit_rate',
            [
                'group' => 'Product Details',
                'label' => 'Credit Rate',
                'type' => 'decimal',
                'input' => 'text',
                'position' => 4,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '1',
                'note' => 'For example: 1.5 -> Each $1 you spend you will get 1.5 credit',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_in_product_listing' => true,
                'apply_to'=>Credit::TYPE_CODE,
            ]
        );

        /*make sure these attributes are applied for membership product type only*/
        $attributes = [
            'credit_rate',
            'credit_price',
            'credit_value_custom',
            'credit_value_dropdown',
            'credit_value_fixed',
            'credit_type',
        ];
        foreach ($attributes as $attributeCode) {
            $categorySetup->updateAttribute(Product::ENTITY, $attributeCode, 'apply_to', Credit::TYPE_CODE);
        }

        $attributes = [
            'weight',
            'tax_class_id',
        ];
        foreach ($attributes as $attributeCode) {
            $relatedProductTypes = explode(
                ',',
                $categorySetup->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attributeCode, 'apply_to')
            );
            if (!in_array(Credit::TYPE_CODE, $relatedProductTypes)) {
                $relatedProductTypes[] = Credit::TYPE_CODE;
                $categorySetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeCode,
                    'apply_to',
                    implode(',', $relatedProductTypes)
                );
            }
        }


        $customerCollection = $this->_customerCollectionFactory->create();
        $data = [];
        $bunchSize = 1000;
        /*Create credit account for all exist customer*/
        $i = 0;
        foreach ($customerCollection as $customer) {
            $data[] = ['customer_id'=>$customer->getId(), 'credit'=>0];
            if ($i ++ >= $bunchSize) {
                $this->insertData($data);
                $data = [];
                $i = 0;
            }
        }
        if (sizeof($data)) {
            $this->insertData($data);
        }

        return $this;
    }

    /**
     * @param $data
     */
    protected function insertData($data)
    {
        $creditModel = $this->_creditFactory->create();
        $tableName = $creditModel->getCollection()->getTable('ves_store_credit');
        $creditModel->getResource()->getConnection()->insertMultiple($tableName, $data);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
