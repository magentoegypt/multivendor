<?php
declare(strict_types=1);

namespace MagentoEgypt\BundleExtend\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

/**
 * Extends the apply_to list of the price / weight attribute family to include the
 * new_bundle type.
 *
 * The earlier patch (ApplyBundleAttributesToNewBundle) only added the bundle-specific
 * *_type toggles (price_type, sku_type, ...). The actual price-bearing attributes are
 * core attributes shared with simple products, and their apply_to listed `bundle` but
 * never `new_bundle`. As a result the Price field (and the whole Advanced Pricing
 * fieldset: Special Price, Tier Price, MSRP, Tax Class) never rendered on a fixed-price
 * new_bundle product form — only the toggles showed. Mirroring `bundle` for these
 * attributes makes the new_bundle form behave like a native bundle.
 */
class ApplyPricingAttributesToNewBundle implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private CategorySetupFactory $categorySetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();
        $setup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Price/weight attributes that native `bundle` has but `new_bundle` was missing.
        $attributes = [
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'tier_price',
            'msrp',
            'msrp_display_actual_price_type',
            'minimal_price',
            'tax_class_id',
            'weight',
        ];

        foreach ($attributes as $code) {
            $attr = $setup->getAttribute(Product::ENTITY, $code);
            if (!$attr) {
                continue;
            }
            // Only extend attributes that already apply to `bundle` — never invent
            // applicability for an attribute that bundle itself doesn't use.
            $applyTo = $attr['apply_to'] ? array_filter(explode(',', $attr['apply_to'])) : [];
            if (!in_array('bundle', $applyTo, true)) {
                continue;
            }
            if (!in_array(BundleExtendHelper::NEW_BUNDLE_TYPE_CODE, $applyTo, true)) {
                $applyTo[] = BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;
                $setup->updateAttribute(Product::ENTITY, $code, 'apply_to', implode(',', $applyTo));
            }
        }

        $this->moduleDataSetup->endSetup();
        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            ApplyBundleAttributesToNewBundle::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
