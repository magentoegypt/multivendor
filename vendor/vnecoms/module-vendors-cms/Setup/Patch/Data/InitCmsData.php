<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsCms\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class add customer updated attribute to customer
 */
class InitCmsData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Vnecoms\VendorsConfig\Model\ResourceModel\Config\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * InitCmsData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Vnecoms\VendorsConfig\Model\ResourceModel\Config\CollectionFactory $collectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Vnecoms\VendorsConfig\Model\ResourceModel\Config\CollectionFactory $collectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup;
        /**
         * change config path vendor cms from "cms/vendor_configs" to "page/cms"
         */
        $where = ['path like ?' => 'cms/vendor_configs%'];
        $setup->getConnection()->update($this->collectionFactory->create()->getMainTable(), ['path' => new \Zend_Db_Expr('REPLACE(path, "cms/vendor_configs","page/cms")')], $where);
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
