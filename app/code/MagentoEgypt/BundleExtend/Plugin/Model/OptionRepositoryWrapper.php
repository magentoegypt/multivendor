<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Bundle\Model\OptionRepository;
use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class OptionRepositoryWrapper
{
    /**
     * @var BundleExtendHelper
     */
    private $helper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        BundleExtendHelper $helper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->helper = $helper;
        $this->productRepository = $productRepository;
    }

    public function aroundGet(OptionRepository $subject, callable $proceed, $sku, $optionId)
    {
        return $this->wrapBySku($sku, fn() => $proceed($sku, $optionId));
    }

    public function aroundGetList(OptionRepository $subject, callable $proceed, $sku)
    {
        return $this->wrapBySku($sku, fn() => $proceed($sku));
    }

    public function aroundDelete(OptionRepository $subject, callable $proceed, OptionInterface $option)
    {
        return $this->wrapBySku($option->getSku(), fn() => $proceed($option));
    }

    public function aroundDeleteById(OptionRepository $subject, callable $proceed, $sku, $optionId)
    {
        return $this->wrapBySku($sku, fn() => $proceed($sku, $optionId));
    }

    public function aroundSave(
        OptionRepository $subject,
        callable $proceed,
        ProductInterface $product,
        OptionInterface $option
    ) {
        if ($product->getTypeId() !== BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return $proceed($product, $option);
        }
        return $this->withFlag(fn() => $proceed($product, $option));
    }

    private function wrapBySku($sku, callable $proceed)
    {
        if (!$this->isNewBundle($sku)) {
            return $proceed();
        }
        return $this->withFlag($proceed);
    }

    private function withFlag(callable $proceed)
    {
        $this->helper->setOverrideTypeIdAsBundle(true);
        try {
            return $proceed();
        } finally {
            $this->helper->setOverrideTypeIdAsBundle(false);
        }
    }

    private function isNewBundle($sku): bool
    {
        if (!$sku) {
            return false;
        }
        try {
            $parent = $this->productRepository->get((string)$sku);
        } catch (\Exception $e) {
            return false;
        }
        // Use getData() to read the raw type_id — getTypeId() is intercepted by
        // OverrideTypeIdAsBundle and returns 'bundle' while the SaveHandler is active.
        return $parent->getData('type_id') === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;
    }
}
