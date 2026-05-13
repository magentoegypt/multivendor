<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsCategory\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class add customer updated attribute to customer
 */
class InitCategoryData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollection;

    /**
     * InitCategoryData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
     * @param \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $factory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory $collectionFactory,
        \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $factory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->collectionFactory = $collectionFactory;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->categoryCollection = $factory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $categorySetup = $this->categorySetupFactory->create(
            ['setup' => $this->moduleDataSetup]
        );

        $vendors = $this->collectionFactory->create();
        $installData = [];
        $setup = $this->moduleDataSetup;
        if ($vendors->count() > 0) {
            foreach ($vendors as $vendor) {
                $select = $setup->getConnection()->select()->from(
                    $setup->getTable('ves_vendorscategory_category'),
                    ['category_id']
                )->where(
                    'level = :level'
                )->where(
                    'vendor_id = :vendor_id'
                );
                $bind = ['level' => 0, 'vendor_id' => $vendor->getId()];

                if ($setup->getConnection()->fetchOne($select, $bind) == false) {
                    $installData[] = [
                        'vendor_id' => $vendor->getId(),
                        'real_category_id' => $vendor->getId(),
                        'name' => __('Root'),
                        'level' => 0,
                        'path' => '',
                        'is_hide_product' => 0,
                        'position' => $vendor->getId(),
                        'parent_id' => 0,
                        'status' => 1,
                        'is_active' => 1,
                        'included_in_menu' => 1,
                        'description' => __('Root'),
                        'children_count' => 0,
                        //  'created_at' => now(),
                        //   'updated_at' => now(),
                    ];
                }
            }

            if (count($installData) > 0) {
                $setup->getConnection()->insertArray(
                    $setup->getTable('ves_vendorscategory_category'),
                    ['vendor_id', 'real_category_id', 'name', 'level', 'path', 'is_hide_product', 'position', 'parent_id', 'status', 'is_active',
                        'included_in_menu', 'description', 'children_count',],
                    $installData
                );
            }

            $collection = $this->categoryCollection->create()->addFieldToFilter('level', 0);

            foreach ($collection as $cat) {
                $cat->setPath($cat->getId())->save();
            }
        }

        $categorySetup->addAttribute(
            Product::ENTITY,
            'ves_category_ids',
            [
                'group' => 'Product Details',
                'label' => 'Vendor Categories',
                'type' => 'static',
                'backend' => 'Vnecoms\VendorsCategory\Model\Product\Entity\Attribute\Backend\Category',
                'input' => 'text',
                'position' => 200,
                'visible' => true,
                'default' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => true, // for test, must change to false in production
                //'source' => 'Vnecoms\VendorsProduct\Model\Source\Approval',
                'default' => '',
                'visible_on_front' => false,
                'unique' => false,
                'is_configurable' => false,
                'used_for_promo_rules' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_in_product_listing' => false,
            ]
        );

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
