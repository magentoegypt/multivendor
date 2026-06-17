<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Bundle\Model\OptionManagement;
use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class OptionManagementWrapper
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

    public function aroundSave(OptionManagement $subject, callable $proceed, OptionInterface $option)
    {
        $sku = $option->getSku();
        $isNewBundle = false;
        if ($sku) {
            try {
                $parent = $this->productRepository->get((string)$sku);
                $isNewBundle = $parent->getTypeId() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;
            } catch (\Exception $e) {
                // ignore
            }
        }

        if (!$isNewBundle) {
            return $proceed($option);
        }

        $this->helper->pushOverrideTypeIdAsBundle(true);
        try {
            return $proceed($option);
        } finally {
            $this->helper->popOverrideTypeIdAsBundle();
        }
    }
}
