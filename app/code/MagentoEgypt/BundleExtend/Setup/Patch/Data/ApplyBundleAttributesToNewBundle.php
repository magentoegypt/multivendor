<?php
declare(strict_types=1);

namespace MagentoEgypt\BundleExtend\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

/**
 * Extends the apply_to list of bundle-specific product attributes to include the
 * new_bundle type so those fields appear in the new_bundle product edit form.
 */
class ApplyBundleAttributesToNewBundle implements DataPatchInterface
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

        // Bundle-specific attributes that must also apply to new_bundle
        $attributes = ['price_type', 'sku_type', 'shipment_type', 'price_view', 'weight_type'];

        foreach ($attributes as $code) {
            $attr = $setup->getAttribute(Product::ENTITY, $code);
            if (!$attr) {
                continue;
            }
            $applyTo = $attr['apply_to'] ? array_filter(explode(',', $attr['apply_to'])) : [];
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
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
