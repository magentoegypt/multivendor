<?php

declare(strict_types=1);

namespace HubMarket\DynamicMenu\Block;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Plugin\Block\Topmenu as CatalogTopmenu;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\View\Element\Template\Context;

/**
 * Uses Magento's native category menu renderer without the global MGS AMP
 * preference that is intended for AMP pages.
 */
class Topmenu extends \Magento\Theme\Block\Html\Topmenu
{
    /**
     * The mega menu's thumbnail for a subcategory with no image of its own is
     * taken from its first product, so this is the size that image is rendered
     * at. `mini_cart_product_thumbnail` because it is the one small square in
     * blank's view.xml and its cache is already generated for the whole
     * catalogue — a new image id would mean a catalog:images:resize run before
     * a single tile appeared.
     */
    private const MEGA_IMAGE_ID = 'mini_cart_product_thumbnail';

    private CatalogTopmenu $catalogTopmenu;

    private bool $categoryTreePrepared = false;

    /**
     * NOT REQUIRED CONSTRUCTOR ARGUMENTS, deliberately.
     *
     * This store runs production mode against a compiled DI config, and that
     * config already holds this class's signature. A compiled factory calls the
     * constructor with the arguments it was compiled for, so anything added
     * here arrives as null until the next di:compile — which is why these are
     * optional and fall back to the object manager. Nothing else about the
     * class changes, so the compiled entry stays valid.
     */
    private ?ResourceConnection $resourceConnection;

    private ?ProductCollectionFactory $productCollectionFactory;

    private ?ImageHelper $imageHelper;

    /** @var array<int, string>|null */
    private ?array $megaThumbnails = null;

    public function __construct(
        Context $context,
        NodeFactory $nodeFactory,
        TreeFactory $treeFactory,
        CatalogTopmenu $catalogTopmenu,
        array $data = [],
        ?ResourceConnection $resourceConnection = null,
        ?ProductCollectionFactory $productCollectionFactory = null,
        ?ImageHelper $imageHelper = null
    ) {
        parent::__construct($context, $nodeFactory, $treeFactory, $data);
        $this->catalogTopmenu = $catalogTopmenu;
        $this->resourceConnection = $resourceConnection ?: ObjectManager::getInstance()->get(ResourceConnection::class);
        $this->productCollectionFactory = $productCollectionFactory
            ?: ObjectManager::getInstance()->get(ProductCollectionFactory::class);
        $this->imageHelper = $imageHelper ?: ObjectManager::getInstance()->get(ImageHelper::class);
    }

    /**
     * A picture for each subcategory in the mega menu, keyed by category id.
     *
     * The catalogue has none of its own: every category that opens a mega panel
     * has children with products but no `image` attribute set, so asking only
     * for the category image would have shipped a menu of empty frames. The
     * category's own image is still preferred when a merchant sets one; the
     * first product in the category is the fallback, which is what makes the
     * tile relevant to the link beside it rather than decorative.
     *
     * Two queries and one collection load for the WHOLE menu, not per column —
     * the template collects every id first and asks once.
     *
     * @param int[] $categoryIds
     * @return array<int, string> category id => image URL
     */
    public function getCategoryThumbnails(array $categoryIds): array
    {
        if ($this->megaThumbnails !== null) {
            return $this->megaThumbnails;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (!$ids) {
            return $this->megaThumbnails = [];
        }

        $this->megaThumbnails = $this->categoryOwnImages($ids);

        $missing = array_values(array_diff($ids, array_keys($this->megaThumbnails)));
        if ($missing) {
            $this->megaThumbnails += $this->firstProductImages($missing);
        }

        return $this->megaThumbnails;
    }

    /**
     * The `image` attribute where one is set, read at store scope with the
     * default-scope row as the fallback — the same precedence EAV itself uses.
     *
     * @param int[] $ids
     * @return array<int, string>
     */
    private function categoryOwnImages(array $ids): array
    {
        $connection = $this->resourceConnection->getConnection();
        $storeId = (int) $this->_storeManager->getStore()->getId();

        $select = $connection->select()
            ->from(['v' => $this->resourceConnection->getTableName('catalog_category_entity_varchar')], ['entity_id', 'store_id', 'value'])
            ->join(
                ['a' => $this->resourceConnection->getTableName('eav_attribute')],
                'a.attribute_id = v.attribute_id',
                []
            )
            ->join(
                ['t' => $this->resourceConnection->getTableName('eav_entity_type')],
                't.entity_type_id = a.entity_type_id',
                []
            )
            ->where('t.entity_type_code = ?', 'catalog_category')
            ->where('a.attribute_code = ?', 'image')
            ->where('v.entity_id IN (?)', $ids)
            ->where('v.store_id IN (?)', [0, $storeId])
            ->order('v.store_id ASC');   // store row read last, so it overwrites

        $out = [];
        foreach ($connection->fetchAll($select) as $row) {
            $value = trim((string) $row['value']);
            if ($value === '') {
                continue;
            }
            $out[(int) $row['entity_id']] = $this->categoryImageUrl($value);
        }

        return $out;
    }

    /**
     * Admin writes this attribute two ways depending on which form saved it: a
     * bare file name, or a path already rooted at the web root
     * (`/media/catalog/category/x.jpg`). Both are in this catalogue.
     */
    private function categoryImageUrl(string $value): string
    {
        if (str_contains($value, '/media/')) {
            return $value;
        }

        return $this->_storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            . 'catalog/category/' . ltrim($value, '/');
    }

    /**
     * The first product of each category, by the position the merchant set.
     *
     * @param int[] $ids
     * @return array<int, string>
     */
    private function firstProductImages(array $ids): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_category_product'), ['category_id', 'product_id'])
            ->where('category_id IN (?)', $ids)
            ->order(['category_id ASC', 'position ASC', 'product_id ASC']);

        $firstProduct = [];
        foreach ($connection->fetchAll($select) as $row) {
            $categoryId = (int) $row['category_id'];
            if (!isset($firstProduct[$categoryId])) {
                $firstProduct[$categoryId] = (int) $row['product_id'];
            }
        }

        if (!$firstProduct) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId((int) $this->_storeManager->getStore()->getId())
            ->addAttributeToSelect(['small_image', 'thumbnail', 'image'])
            ->addFieldToFilter('entity_id', ['in' => array_values($firstProduct)]);

        $byProduct = [];
        foreach ($collection as $product) {
            $byProduct[(int) $product->getId()] = $this->imageHelper
                ->init($product, self::MEGA_IMAGE_ID)
                ->getUrl();
        }

        $out = [];
        foreach ($firstProduct as $categoryId => $productId) {
            if (isset($byProduct[$productId])) {
                $out[$categoryId] = $byProduct[$productId];
            }
        }

        return $out;
    }

    /**
     * Populate the tree through Magento_Catalog directly. The catalog plugin is
     * normally attached to Magento's Topmenu by DI, but MGS_Amp replaces that
     * class globally and prevents the native renderer from being used.
     */
    public function getHtml($outermostClass = '', $childrenWrapClass = '', $limit = 0)
    {
        if (!$this->categoryTreePrepared) {
            $this->catalogTopmenu->beforeGetHtml(
                $this,
                $outermostClass,
                $childrenWrapClass,
                $limit
            );
            $this->categoryTreePrepared = true;
        }

        return parent::getHtml($outermostClass, $childrenWrapClass, $limit);
    }

    public function getIdentities()
    {
        $this->catalogTopmenu->beforeGetIdentities($this);

        return parent::getIdentities();
    }
}
