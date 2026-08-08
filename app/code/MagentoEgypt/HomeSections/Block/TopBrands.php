<?php
/**
 * Hub Market — "Top Brands" row.
 *
 * Reads the real MGS_Brand catalogue (29 brands, 190 product links) rather than
 * a hand-maintained list. Brands are store-scoped through mgs_brand_store, so a
 * brand that is not assigned to the current store view does not appear.
 *
 * NOTE ON THE MODULE DEPENDENCY: MGS_Brand was disabled during the Phase L
 * decommission and re-enabled specifically to back this section. It is the only
 * MGS module besides MGS_GDPR still running. The brand DATA (mgs_brand,
 * mgs_brand_product) survived the decommission untouched because disabling a
 * module never drops its tables.
 *
 * The query is deliberately direct SQL rather than the MGS collection: MGS_Brand
 * is a retired-vendor module and coupling our homepage to its model layer would
 * make it harder to replace later. The table shape is stable and tiny.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class TopBrands extends Template
{
    private ResourceConnection $resource;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * @return array<int, array{name:string,url:string,image:?string}>
     */
    public function getBrands(): array
    {
        $limit = (int) ($this->getData('limit') ?: 10);
        $storeId = (int) $this->storeManager->getStore()->getId();

        $conn = $this->resource->getConnection();
        $brand = $this->resource->getTableName('mgs_brand');
        $store = $this->resource->getTableName('mgs_brand_store');

        // Guard: if MGS_Brand is ever disabled again the tables remain but the
        // section should simply not render rather than fatal.
        if (!$conn->isTableExists($brand)) {
            return [];
        }

        $select = $conn->select()
            ->from(['b' => $brand], ['brand_id', 'name', 'url_key', 'small_image', 'image'])
            ->where('b.status = ?', 1)
            ->order('b.sort_order ASC')
            ->limit($limit);

        if ($conn->isTableExists($store)) {
            $select->join(['s' => $store], 's.brand_id = b.brand_id', [])
                   ->where('s.store_id IN (?)', [0, $storeId])
                   ->group('b.brand_id');
        }

        $mediaUrl = $this->storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $out = [];
        foreach ($conn->fetchAll($select) as $row) {
            $img = $row['small_image'] ?: $row['image'];
            $out[] = [
                'name'  => (string) $row['name'],
                'url'   => $this->getUrl('brand/index/view', ['id' => $row['brand_id']]),
                'image' => $img ? $mediaUrl . ltrim((string) $img, '/') : null,
            ];
        }

        return $out;
    }

    public function getCacheKeyInfo(): array
    {
        return ['HM_HOME_TOP_BRANDS', $this->storeManager->getStore()->getId(),
                (int) ($this->getData('limit') ?: 10)];
    }

    protected function getCacheLifetime(): ?int
    {
        return 3600;
    }
}
