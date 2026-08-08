<?php
/**
 * Adds a seller-controlled `dispatch_time` attribute to the Vnecoms vendor entity.
 *
 * WHY: the homepage store cards previously showed a DERIVED dispatch time,
 * averaged from order -> shipment timestamps. That describes what a seller did
 * historically, not what they promise, and the raw data here ranged from 0 hours
 * to 69 days because the orders are demo data. A promise has to be made by the
 * seller, not inferred on their behalf.
 *
 * THIS PATCH ALONE DOES NOT PUT THE FIELD ON ANY FORM. An earlier version of this
 * comment claimed Vnecoms picks up `visible` + `user_defined` attributes
 * automatically. It does not, and the field was invisible to sellers as a result.
 * Vnecoms gates the seller form on TWO things this patch does not set:
 *
 *   1. ves_vendor_eav_attribute.is_used_in_profile_form = 1
 *      (Block\Vendors\Account\Edit\Form\Attributes::_prepareForm)
 *   2. membership of a `ves_vendor_fieldset_attr` row pointing at a fieldset
 *      whose `form` is profile_form — Vnecoms uses its OWN fieldset tables here,
 *      NOT the EAV attribute set, so eav_entity_attribute is irrelevant.
 *
 * Both are done by AddDispatchTimeToVendorForm.
 *
 * A select, not free text: "2-3 business days" is comparable across stores and
 * translatable; a text field would produce forty different phrasings.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Vnecoms\Vendors\Model\Vendor;
use Vnecoms\Vendors\Setup\VendorSetupFactory;

class AddVendorDispatchTime implements DataPatchInterface
{
    public const ATTRIBUTE = 'dispatch_time';

    private ModuleDataSetupInterface $moduleDataSetup;
    private VendorSetupFactory $vendorSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        VendorSetupFactory $vendorSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->vendorSetupFactory = $vendorSetupFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $vendorSetup = $this->vendorSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $vendorSetup->addAttribute(
            Vendor::ENTITY,
            self::ATTRIBUTE,
            [
                'label'        => 'Dispatch Time',
                'note'         => 'How quickly you normally hand orders to the courier. Shown on your store card.',
                'type'         => 'varchar',
                'input'        => 'select',
                'source'       => \MagentoEgypt\HomeSections\Model\Vendor\Source\DispatchTime::class,
                'position'     => 200,
                'visible'      => true,
                'required'     => false,
                'default'      => '',
                'user_defined' => 1,
                'system'       => 0,
            ]
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
