<?php
namespace MagentoEgypt\BundleExtend\Ui\DataProvider\Product\Form\Modifier;

use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\BundlePanel;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

/**
 * Replaces Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\Composite so that
 * the bundle admin form modifiers fire for both `bundle` and `new_bundle` product types.
 */
class Composite extends \Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\Composite
{
    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->isBundleLike()) {
            return $meta;
        }

        foreach ($this->modifiers as $bundleClass) {
            /** @var ModifierInterface $bundleModifier */
            $bundleModifier = $this->objectManager->get($bundleClass);
            if (!$bundleModifier instanceof ModifierInterface) {
                throw new \InvalidArgumentException(
                    'Type "' . $bundleClass . '" is not an instance of ' . ModifierInterface::class
                );
            }
            $meta = $bundleModifier->modifyMeta($meta);
        }

        return $this->stripPhantomTierPrice($meta);
    }

    /**
     * Remove a config-less "tier_price" node from the Advanced Pricing fieldset.
     *
     * Magento\Bundle\...\Modifier\BundleAdvancedPricing::modifyMeta() accesses
     * $node['tier_price']['children'] by reference. For a real `bundle` the tier_price
     * attribute is in apply_to so that node already exists; for `new_bundle` it is NOT,
     * so the reference auto-vivifies a phantom ['tier_price' => ['children' => null]] with
     * no `componentType`. UiComponentFactory::mergeMetadataItem() then throws
     * "The componentType configuration parameter is required for the tier_price component"
     * and the whole new_bundle product form errors out. Strip the phantom so the form renders.
     */
    private function stripPhantomTierPrice(array $meta): array
    {
        /** @var ArrayManager $arrayManager */
        $arrayManager = $this->objectManager->get(ArrayManager::class);
        $tierPricePath = $arrayManager->findPath(
            ProductAttributeInterface::CODE_TIER_PRICE,
            $meta,
            null,
            'children'
        );

        if ($tierPricePath
            && !$arrayManager->get($tierPricePath . '/arguments/data/config/componentType', $meta)
        ) {
            $meta = $arrayManager->remove($tierPricePath, $meta);
        }

        return $meta;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function modifyData(array $data)
    {
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $this->locator->getProduct();
        $modelId = $product->getId();
        if (!$this->isBundleLike() || !$modelId) {
            return $data;
        }

        $data[$modelId][BundlePanel::CODE_BUNDLE_OPTIONS][BundlePanel::CODE_BUNDLE_OPTIONS] = [];
        foreach ($this->optionsRepository->getList($product->getSku()) as $option) {
            $selections = [];
            foreach ($option->getProductLinks() as $productLink) {
                $linkedProduct = $this->productRepository->get($productLink->getSku());
                $integerQty = 1;
                if ($linkedProduct->getExtensionAttributes()->getStockItem()) {
                    if ($linkedProduct->getExtensionAttributes()->getStockItem()->getIsQtyDecimal()) {
                        $integerQty = 0;
                    }
                }
                $selections[] = [
                    'selection_id' => $productLink->getId(),
                    'option_id' => $productLink->getOptionId(),
                    'product_id' => $linkedProduct->getId(),
                    'name' => $linkedProduct->getName(),
                    'sku' => $linkedProduct->getSku(),
                    'is_default' => ($productLink->getIsDefault()) ? '1' : '0',
                    'selection_price_value' => $productLink->getPrice(),
                    'selection_price_type' => $productLink->getPriceType(),
                    'selection_qty' => $integerQty ? (int)$productLink->getQty() : $productLink->getQty(),
                    'selection_can_change_qty' => $productLink->getCanChangeQuantity(),
                    'selection_qty_is_integer' => (bool)$integerQty,
                    'position' => $productLink->getPosition(),
                    'delete' => '',
                ];
            }
            $data[$modelId][BundlePanel::CODE_BUNDLE_OPTIONS][BundlePanel::CODE_BUNDLE_OPTIONS][] = [
                'position' => $option->getPosition(),
                'option_id' => $option->getOptionId(),
                'title' => $option->getTitle(),
                'default_title' => $option->getDefaultTitle(),
                'type' => $option->getType(),
                'required' => ($option->getRequired()) ? '1' : '0',
                'bundle_selections' => $selections,
            ];
        }

        return $data;
    }

    private function isBundleLike(): bool
    {
        $typeId = $this->locator->getProduct()->getTypeId();
        return $typeId === Type::TYPE_CODE
            || $typeId === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;
    }
}
