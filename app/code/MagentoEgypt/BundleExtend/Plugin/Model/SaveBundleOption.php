<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class SaveBundleOption
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        ResourceConnection $resource,
        ProductRepositoryInterface $productRepository
    ) {
        $this->resource = $resource;
        $this->productRepository = $productRepository;
    }

    public function afterSave(
        \Magento\Bundle\Api\ProductOptionRepositoryInterface $subject,
        $result,
        $option
    ) {
        if (!$option->getOptionId()) {
            return $result;
        }

        if (!$this->isNewBundleParent($option->getSku())) {
            return $result;
        }

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_product_bundle_option');

        $connection->update(
            $table,
            [
                'discount_type' => $option->getData('discount_type'),
                'discount_amount' => $option->getData('discount_amount')
            ],
            ['option_id = ?' => $option->getOptionId()]
        );

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
        return $parent->getTypeId() === BundleExtendHelper::NEW_BUNDLE_TYPE_CODE;
    }
}
