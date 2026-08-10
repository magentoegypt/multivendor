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
    /** Used when layout supplies no glyph for a category's URL key. */
    private const FALLBACK_ICON = '🏷️';

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
        $collection->addAttributeToSelect(['name', 'url_key'])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('include_in_menu', 1)
            ->setStoreId((int) $this->storeManager->getStore()->getId())
            ->addFieldToFilter('level', 2)
            ->setLoadProductCount(true)
            ->addAttributeToSort('position', 'ASC');

        $icons = (array) ($this->getData('icons') ?: []);
        $tints = (array) ($this->getData('tints') ?: []);

        $out   = [];
        $index = 0;
        foreach ($collection as $category) {
            $count = (int) $category->getProductCount();
            if ($count < 1) {
                continue;
            }
            $urlKey = (string) $category->getUrlKey();
            $out[]  = [
                'id'    => (int) $category->getId(),
                'name'  => (string) $category->getName(),
                'url'   => $category->getUrl(),
                'count' => $count,
                /*
                 * Figma's chip carries a flat glyph, not a photograph, and that is
                 * the better source here as well as the matching one: the category
                 * images on this catalog are cropped merchandising shots that read
                 * as noise at 40px — "shoes" renders as a dog, "computer" as a
                 * beach. Keyed on URL KEY because it is stable across store views,
                 * unlike the translated name.
                 */
                'icon'  => $icons[$urlKey] ?? self::FALLBACK_ICON,
                /*
                 * Tint slot, keyed on URL KEY like the glyph above, with the
                 * position as a fallback.
                 *
                 * Figma assigns its eight pastels per CATEGORY, not per slot, and
                 * that difference is not cosmetic: with a positional modulo,
                 * disabling or adding one category re-colours every chip after it
                 * in the row. Keyed on the category, a chip keeps its colour for
                 * as long as it exists.
                 */
                'tint'  => array_key_exists($urlKey, $tints) ? (int) $tints[$urlKey] % 8 : $index % 8,
            ];
            $index++;
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
            /*
             * The glyph map is part of the rendered output, so it has to be part of
             * the key — otherwise editing layout leaves the cached row showing the
             * old icons until the block cache happens to expire.
             */
            md5(json_encode([$this->getData('icons') ?: [], $this->getData('tints') ?: []])),
        ];
    }

    protected function getCacheLifetime(): ?int
    {
        return 3600;
    }
}
