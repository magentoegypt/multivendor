<?php
/**
 * Puts `dispatch_time` on the seller's own profile form.
 *
 * AddVendorDispatchTime created the attribute, but creating a vendor attribute is
 * not enough to make Vnecoms render it — the seller panel form
 * (Vnecoms\Vendors\Block\Vendors\Account\Edit\Form\Attributes::_prepareForm)
 * skips any attribute that fails EITHER of these:
 *
 *   - $attribute->getIsUsedInProfileForm()   -> ves_vendor_eav_attribute flag
 *   - membership of the fieldset being rendered
 *
 * The fieldset is the part that is easy to get wrong. Vnecoms does NOT use the
 * EAV attribute set / group for this. It has its own pair of tables —
 * ves_vendor_fieldset (one row per tab, keyed by `form`) and
 * ves_vendor_fieldset_attr (the attribute mapping) — and Tabs::_prepareLayout
 * filters them on form = profile_form. An attribute absent from
 * ves_vendor_fieldset_attr renders nowhere no matter what its flags say.
 *
 * Registration is deliberately left alone: dispatch time is an operational
 * commitment a seller should be able to revisit, not a question to answer while
 * signing up. Adding it there means inserting into the registration_form fieldset
 * and setting is_used_in_registration_form.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Vnecoms\Vendors\Helper\Data as VendorsHelper;

class AddDispatchTimeToVendorForm implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $conn = $this->moduleDataSetup->getConnection();

        // Two plain lookups rather than a nested select: embedding a Select via
        // Zend_Db_Expr interpolates it WITHOUT parentheses, producing
        // `entity_type_id = SELECT ...` and a 1064 syntax error.
        $entityTypeId = (int) $conn->fetchOne(
            $conn->select()
                ->from($this->moduleDataSetup->getTable('eav_entity_type'), ['entity_type_id'])
                ->where('entity_type_code = ?', \Vnecoms\Vendors\Model\Vendor::ENTITY)
        );

        $attributeId = $entityTypeId ? (int) $conn->fetchOne(
            $conn->select()
                ->from($this->moduleDataSetup->getTable('eav_attribute'), ['attribute_id'])
                ->where('attribute_code = ?', AddVendorDispatchTime::ATTRIBUTE)
                ->where('entity_type_id = ?', $entityTypeId)
        ) : 0;

        if (!$attributeId) {
            // Nothing to wire up; AddVendorDispatchTime has not run.
            $this->moduleDataSetup->endSetup();
            return $this;
        }

        // 1. let the profile form consider it at all
        $conn->update(
            $this->moduleDataSetup->getTable('ves_vendor_eav_attribute'),
            ['is_used_in_profile_form' => 1],
            ['attribute_id = ?' => $attributeId]
        );

        // 2. place it in the FIRST profile-form fieldset ("General"). Resolved by
        //    query rather than hard-coded: fieldset ids are install-specific.
        $fieldsetTable = $this->moduleDataSetup->getTable('ves_vendor_fieldset');
        $fieldsetId = (int) $conn->fetchOne(
            $conn->select()
                ->from($fieldsetTable, ['fieldset_id'])
                ->where('form = ?', VendorsHelper::PROFILE_FORM)
                ->order('sort_order ASC')
                ->limit(1)
        );

        if ($fieldsetId) {
            $mapTable = $this->moduleDataSetup->getTable('ves_vendor_fieldset_attr');

            $exists = $conn->fetchOne(
                $conn->select()
                    ->from($mapTable, ['id'])
                    ->where('fieldset_id = ?', $fieldsetId)
                    ->where('attribute_id = ?', $attributeId)
            );

            if (!$exists) {
                $conn->insert($mapTable, [
                    'fieldset_id'  => $fieldsetId,
                    'attribute_id' => $attributeId,
                    'sort_order'   => 200,
                ]);
            }
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [AddVendorDispatchTime::class];
    }

    public function getAliases()
    {
        return [];
    }
}
