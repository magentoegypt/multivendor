<?php
namespace MagentoEgypt\BundleExtend\Plugin;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product as CatalogProduct;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

/**
 * Mirrors Magento\Bundle\Model\Plugin\ProductIdentitiesExtender for new_bundle.
 * Adds parent identities to new_bundle product identities (matches Bundle's behaviour,
 * which uses Bundle's selection table — shared with new_bundle).
 */
class ProductIdentitiesExtender
{
    /**
     * @var BundleType
     */
    private $type;

    /**
     * @var array
     */
    private $cacheParentIdsByChild = [];

    public function __construct(BundleType $type)
    {
        $this->type = $type;
    }

    /**
     * @param CatalogProduct $product
     * @param array $identities
     * @return string[]
     */
    public function afterGetIdentities(CatalogProduct $product, array $identities)
    {
        if ($product->getTypeId() !== BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return $identities;
        }
        foreach ($this->getParentIdsByChild($product->getEntityId()) as $parentId) {
            $identities[] = CatalogProduct::CACHE_TAG . '_' . $parentId;
        }
        return $identities;
    }

    /**
     * @param mixed $entityId
     * @return array
     */
    private function getParentIdsByChild($entityId): array
    {
        if (!isset($this->cacheParentIdsByChild[$entityId])) {
            $this->cacheParentIdsByChild[$entityId] = $this->type->getParentIdsByChild($entityId);
        }
        return $this->cacheParentIdsByChild[$entityId];
    }
}
