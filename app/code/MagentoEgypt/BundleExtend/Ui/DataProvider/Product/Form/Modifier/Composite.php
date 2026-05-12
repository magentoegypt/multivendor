<?php
namespace MagentoEgypt\BundleExtend\Ui\DataProvider\Product\Form\Modifier;

use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\BundlePanel;
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
