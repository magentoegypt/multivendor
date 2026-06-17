<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Bundle\Model\LinkManagement;
use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class LinkManagementPlugin
{
    /**
     * @var BundleExtendHelper
     */
    protected $bundleExtendHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        BundleExtendHelper $bundleExtendHelper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->bundleExtendHelper = $bundleExtendHelper;
        $this->productRepository = $productRepository;
    }

    public function beforeSaveChild(LinkManagement $subject, $sku, LinkInterface $linkedProduct)
    {
        $this->pushFlags($this->isNewBundleParent($sku));
        return [$sku, $linkedProduct];
    }

    public function afterSaveChild(LinkManagement $subject, $result)
    {
        $this->popFlags();
        return $result;
    }

    public function beforeAddChild(LinkManagement $subject, ProductInterface $product, $optionId, LinkInterface $linkedProduct)
    {
        $this->pushFlags($product->getData('type_id') === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE);
        return [$product, $optionId, $linkedProduct];
    }

    public function afterAddChild(LinkManagement $subject, $result)
    {
        $this->popFlags();
        return $result;
    }

    /**
     * Push the flags for one LinkManagement call. Always pushes (so the matching
     * afterX() always pops) — when the parent is a new_bundle we force the flags on,
     * otherwise we re-push whatever an outer scope already had so the pop is a no-op.
     */
    private function pushFlags(bool $isNewBundle): void
    {
        $this->bundleExtendHelper->pushSkipComplexCheck(
            $isNewBundle ? true : $this->bundleExtendHelper->getSkipComplexCheck()
        );
        $this->bundleExtendHelper->pushOverrideTypeIdAsBundle(
            $isNewBundle ? true : $this->bundleExtendHelper->getOverrideTypeIdAsBundle()
        );
    }

    private function popFlags(): void
    {
        $this->bundleExtendHelper->popOverrideTypeIdAsBundle();
        $this->bundleExtendHelper->popSkipComplexCheck();
    }

    private function isNewBundleParent($sku): bool
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
