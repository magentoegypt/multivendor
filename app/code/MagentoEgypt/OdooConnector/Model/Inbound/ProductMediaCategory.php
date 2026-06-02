<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Inbound;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Applies the heavier inbound (O->M) product attributes that don't map to a
 * simple setter: the main image (base64 -> media gallery) and categories
 * (name -> find/create Magento category -> assign). Used by InboundProcessor.
 */
class ProductMediaCategory
{
    private const DEFAULT_PARENT = 2; // Magento "Default Category"

    private Filesystem $filesystem;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryInterfaceFactory $categoryFactory;

    /** @var array<string, int|null> */
    private array $catCache = [];

    public function __construct(
        Filesystem $filesystem,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        CategoryInterfaceFactory $categoryFactory
    ) {
        $this->filesystem = $filesystem;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Fill the product's main image from a base64 blob when it has none
     * (fill-if-missing — avoids piling up a new gallery entry on every sync).
     * Returns true when an image was added.
     */
    public function applyImage(ProductInterface $product, string $base64): bool
    {
        if (trim($base64) === '' || !$product instanceof Product) {
            return false;
        }
        $current = (string)$product->getImage();
        if ($current !== '' && $current !== 'no_selection') {
            return false;
        }
        $bytes = base64_decode($base64, true);
        if ($bytes === false || $bytes === '') {
            return false;
        }
        try {
            // Gallery validation keys off the file extension, so derive it from the bytes.
            $ext = 'jpg';
            $info = @getimagesizefromstring($bytes);
            if (is_array($info) && !empty($info['mime'])) {
                $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                $ext = $map[$info['mime']] ?? 'jpg';
            }
            $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $rel = 'tmp/odoo-sync/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', (string)$product->getSku()) . '.' . $ext;
            $media->writeFile($rel, $bytes);
            $product->addImageToMediaGallery($media->getAbsolutePath($rel), ['image', 'small_image', 'thumbnail'], true, false);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ensure Magento categories exist (by name, under Default Category) and assign
     * them to the product. Returns the assigned category ids.
     *
     * @param array<int, mixed> $names
     * @return int[]
     */
    public function applyCategories(ProductInterface $product, array $names): array
    {
        if (!$product instanceof Product) {
            return [];
        }
        $ids = [];
        foreach ($names as $name) {
            $name = trim((string)$name);
            if ($name === '') {
                continue;
            }
            $id = $this->ensureCategory($name);
            if ($id !== null) {
                $ids[] = $id;
            }
        }
        if ($ids !== []) {
            $product->setCategoryIds(array_values(array_unique($ids)));
        }

        return $ids;
    }

    private function ensureCategory(string $name): ?int
    {
        if (array_key_exists($name, $this->catCache)) {
            return $this->catCache[$name];
        }
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('name', $name)->setPageSize(1);
        $existing = $collection->getFirstItem();
        if ($existing->getId()) {
            return $this->catCache[$name] = (int)$existing->getId();
        }
        try {
            $category = $this->categoryFactory->create();
            $category->setName($name);
            $category->setParentId(self::DEFAULT_PARENT);
            $category->setIsActive(true);
            // This store has a third-party REQUIRED category attribute
            // (app_special_category, boolean) — set it so validation passes on save.
            $category->setData('app_special_category', 1);
            $saved = $this->categoryRepository->save($category);

            return $this->catCache[$name] = (int)$saved->getId();
        } catch (\Throwable $e) {
            return $this->catCache[$name] = null;
        }
    }
}
