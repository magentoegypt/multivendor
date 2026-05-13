<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class add customer updated attribute to customer
 */
class InitProductData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    private $_categorySetupFactory;

    /**
     * InitProductData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
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
            'vendor_id',
            [
                'group' => 'Product Details',
                'label' => 'Vendor Id',
                'type' => 'static',
                'input' => 'text',
                'position' => 145,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => true,
                'is_used_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_in_product_listing' => true,
                'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL
            ]
        );
        $categorySetup->addAttribute(
            Product::ENTITY,
            'approval',
            [
                'group' => 'Product Details',
                'label' => 'Approval',
                'type' => 'int',
                'input' => 'select',
                'position' => 160,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'source' => 'Vnecoms\VendorsProduct\Model\Source\Approval',
                'default' => '',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'used_in_product_listing' => true,
                'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL
            ]
        );

        $categorySetup->updateAttribute(Product::ENTITY, 'approval', 'used_in_product_listing',1);

        return $this;
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
