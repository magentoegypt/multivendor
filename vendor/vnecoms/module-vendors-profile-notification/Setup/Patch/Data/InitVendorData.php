<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsProfileNotification\Setup\Patch\Data;

use Vnecoms\Vendors\Model\Vendor;
use Vnecoms\Vendors\Setup\VendorSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class add customer updated attribute to customer
 */
class InitVendorData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var VendorSetupFactory
     */
    private $vendorSetupFactory;

    /**
     * InitVendorData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param VendorSetupFactory $vendorSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        VendorSetupFactory $vendorSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->vendorSetupFactory = $vendorSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $vendorSetup = $this->vendorSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributes = [
            'telephone',
            'country_id',
            'city',
            'street',
        ];
        foreach($attributes as $attribute){
            $attr = $vendorSetup->getAttribute(Vendor::ENTITY, $attribute);
            if (!$attr) continue;
            $vendorSetup->updateAttribute(Vendor::ENTITY, $attribute, 'is_required',0);
        }

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
