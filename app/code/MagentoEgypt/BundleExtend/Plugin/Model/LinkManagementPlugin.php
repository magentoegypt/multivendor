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
        if ($this->isNewBundleParent($sku)) {
            $this->bundleExtendHelper->setSkipComplexCheck(true);
            $this->bundleExtendHelper->setOverrideTypeIdAsBundle(true);
        }
        return [$sku, $linkedProduct];
    }

    public function afterSaveChild(LinkManagement $subject, $result)
    {
        $this->bundleExtendHelper->setSkipComplexCheck(false);
        $this->bundleExtendHelper->setOverrideTypeIdAsBundle(false);
        return $result;
    }

    public function beforeAddChild(LinkManagement $subject, ProductInterface $product, $optionId, LinkInterface $linkedProduct)
    {
        if ($product->getTypeId() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            $this->bundleExtendHelper->setSkipComplexCheck(true);
            $this->bundleExtendHelper->setOverrideTypeIdAsBundle(true);
        }
        return [$product, $optionId, $linkedProduct];
    }

    public function afterAddChild(LinkManagement $subject, $result)
    {
        $this->bundleExtendHelper->setSkipComplexCheck(false);
        $this->bundleExtendHelper->setOverrideTypeIdAsBundle(false);
        return $result;
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
