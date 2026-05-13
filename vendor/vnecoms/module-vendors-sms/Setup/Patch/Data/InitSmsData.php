<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSms\Setup\Patch\Data;

use Vnecoms\Vendors\Model\Vendor;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Vnecoms\Vendors\Setup\VendorSetupFactory;

/**
 * Class add customer updated attribute to customer
 */
class InitSmsData implements DataPatchInterface, PatchVersionInterface
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
     * InitSmsData constructor.
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
        $vendorSetup = $this->vendorSetupFactory->create(
            ['setup' => $this->moduleDataSetup]
        );


        $vendorSetup->addAttribute(
            Vendor::ENTITY,
            'sms_credit',
            [
                'label'     => 'Sms Credit',
                'type'      => 'static',
                'input'     => 'text',
                'position'  => 145,
                'visible'   => true,
                'required'  => false,
                'default'   => '',
                'system'    => false,
                'user_defined'              => 0,
                'used_in_profile_form'      => 0,
                'used_in_registration_form' => 0,
                'visible_in_customer_form'  => 0,
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
