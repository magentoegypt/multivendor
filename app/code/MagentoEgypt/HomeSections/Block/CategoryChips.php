<?php
/**
 * Hub Market — homepage category chips.
 *
 * The Figma reference shows a row of category tiles each carrying a live item
 * count ("Grocery — 2,400+ items"). The count is the whole point of the
 * component: a static CMS block cannot produce it and would go stale the moment
 * the catalog changes, so this reads the real category tree instead.
 *
 * Counts come from the category's own product count, which Magento maintains on
 * the collection — no per-category COUNT(*) queries.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class CategoryChips extends Template
{
    private CollectionFactory $collectionFactory;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Top-level, active, menu-visible categories that actually have products.
     *
     * `is_active` alone is not enough — this catalog carries several empty
     * scaffolding categories (Promotions, Sale) that would render as chips
     * leading to an empty listing. Filtering on the product count keeps the row
     * honest.
     *
     * @return array<int, array{id:int,name:string,url:string,count:int,image:?string}>
     */
    public function getCategories(): array
    {
        $limit = (int) ($this->getData('limit') ?: 8);

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect(['name', 'image', 'thumbnail'])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('include_in_menu', 1)
            ->setStoreId((int) $this->storeManager->getStore()->getId())
            ->addFieldToFilter('level', 2)
            ->setLoadProductCount(true)
            ->addAttributeToSort('position', 'ASC');

        $out = [];
        foreach ($collection as $category) {
            $count = (int) $category->getProductCount();
            if ($count < 1) {
                continue;
            }
            $out[] = [
                'id'    => (int) $category->getId(),
                'name'  => (string) $category->getName(),
                'url'   => $category->getUrl(),
                'count' => $count,
                'image' => $category->getImageUrl() ?: null,
            ];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * Cache the rendered row per store. Category counts change only on reindex,
     * and this block sits on the highest-traffic page on the site.
     */
    public function getCacheKeyInfo(): array
    {
        return [
            'HM_HOME_CATEGORY_CHIPS',
            $this->storeManager->getStore()->getId(),
            (int) ($this->getData('limit') ?: 8),
        ];
    }

    protected function getCacheLifetime(): ?int
    {
        return 3600;
    }
}
