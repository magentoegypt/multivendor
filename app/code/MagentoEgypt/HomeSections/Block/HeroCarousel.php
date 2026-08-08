<?php
/**
 * Hub Market — hero carousel.
 *
 * Slides are REAL top-level categories that have both a category image on disk
 * and products behind them. Nothing is invented: the headline is the category
 * name, the image is the merchandiser's own category image, and the CTA goes to
 * that category's URL.
 *
 * Fifteen top-level categories carry a usable image on this install; the block
 * takes the busiest few by product count so the hero always leads with
 * somewhere worth landing.
 *
 * The image URL comes from the category model's getImageUrl(), NOT from
 * concatenating the raw attribute value. The stored value already contains a
 * /media/catalog/category/ prefix, so building the path by hand double-prefixes
 * it and every image appears to be missing — which is exactly the wrong
 * conclusion I drew before checking.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class HeroCarousel extends Template
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
     * @return array<int, array{name:string,url:string,image:string,count:int}>
     */
    public function getSlides(): array
    {
        $limit = (int) ($this->getData('limit') ?: 4);

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect(['name', 'image', 'url_key'])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('include_in_menu', 1)
            ->setStoreId((int) $this->storeManager->getStore()->getId())
            ->addFieldToFilter('level', 2)
            ->setLoadProductCount(true);

        $slides = [];
        foreach ($collection as $category) {
            $image = $category->getImageUrl();
            $count = (int) $category->getProductCount();
            if (!$image || $count < 1) {
                continue;
            }
            $urlKey = (string) $category->getUrlKey();
            $copy   = $this->getCopyFor($urlKey);

            $slides[] = [
                'name'  => (string) $category->getName(),
                'url'   => $category->getUrl(),
                'image' => $image,
                'count' => $count,
                'key'   => $urlKey,
                // Figma writes a kicker / headline / subtext / CTA per slide rather
                // than just the category name. Any of these may be absent, and the
                // template falls back to the data-driven values when they are.
                'kicker'   => $copy['kicker']   ?? null,
                'headline' => $copy['headline'] ?? null,
                'subtext'  => $copy['subtext']  ?? null,
                'cta'      => $copy['cta']      ?? null,
            ];
        }

        // Busiest categories first — the hero should lead somewhere stocked.
        usort($slides, static fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($slides, 0, $limit);
    }

    /**
     * Per-slide marketing copy, keyed by category url-key.
     *
     * Supplied as a LAYOUT ARGUMENT (see cms_index_index.xml), not hard-coded here,
     * so the copy can be rewritten without touching PHP and stays translatable —
     * the template runs each string through __(), so ar_SA.csv covers it.
     *
     * A category with no entry keeps the data-driven treatment: live item count as
     * the kicker and the category name as the headline. That matters because the
     * slide set is chosen by product count, so which categories appear can change.
     *
     * @return array<string,string>
     */
    private function getCopyFor(string $urlKey): array
    {
        $all = $this->getData('slide_copy');
        if (!is_array($all) || $urlKey === '') {
            return [];
        }
        $entry = $all[$urlKey] ?? null;

        return is_array($entry) ? $entry : [];
    }

    public function getCacheKeyInfo(): array
    {
        return [
            'HM_HOME_HERO_CAROUSEL',
            $this->storeManager->getStore()->getId(),
            (int) ($this->getData('limit') ?: 4),
        ];
    }

    protected function getCacheLifetime(): ?int
    {
        return 3600;
    }
}
