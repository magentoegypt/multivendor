<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\CacheContextFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\VendorExtend\Api\Data\Product\StoreTranslationInterface;
use MagentoEgypt\VendorExtend\Api\Data\Product\StoreTranslationInterfaceFactory;
use MagentoEgypt\VendorExtend\Api\Data\Product\TranslationsInterface;
use MagentoEgypt\VendorExtend\Api\Data\Product\TranslationsInterfaceFactory;
use MagentoEgypt\VendorExtend\Api\ProductTranslationsInterface;
use MagentoEgypt\VendorExtend\Plugin\VendorsApi\VendorAccessGuard;
use Psr\Log\LoggerInterface;
use Vnecoms\VendorsProduct\Helper\Data as VendorProductHelper;
use Vnecoms\VendorsProduct\Model\Product\Update;
use Vnecoms\VendorsProduct\Model\Product\UpdateFactory;
use Vnecoms\VendorsProduct\Model\Source\Approval;

/**
 * Store-view text for the vendor app's product form (backend handoff 2026-10-03).
 *
 * The app's product PUT saves at store 0, "one value per field for every language", so a seller could not see
 * or edit a product's Arabic (or English) text, and an edit made in the Arabic app overwrote the default.
 * This reads and writes each store view's OWN value of the translatable attributes.
 *
 * `stores` lists EVERY store view of the product's websites, en as well as ar. The spec assumed store 0 is
 * English and only ar is a translation. That holds for ~300 products, but ~214 of the real catalogue keep
 * ARABIC at store 0 with their English name at en (store 3; written on 2026-08-19 and since). Listing en lets
 * the app edit those correctly, and nothing deletes en values.
 *
 * Writes follow the same approval rule as ProductRepository::update(). When updates need approval and the
 * product is past review (Approved / Pending Update), the change is queued as a Pending Update with that
 * store's store_id, merged into one already waiting for the same store; Vnecoms Approve / MassApprove and the
 * admin apply-on-save (Observer\PendingProductUpdate) apply it at that store. Otherwise it is saved at once.
 * A null value removes the store's own value, so the store falls back to the default. Saved directly, the row
 * is deleted (a NULL row would HIDE the default, not fall back). Queued, Approve's save at the store with the
 * attribute null deletes it too (checked 2026-10-03).
 */
class ProductTranslations implements ProductTranslationsInterface
{
    /**
     * Attributes the form may translate. Each must ALSO be store-scoped text/textarea and editable by sellers.
     * Not the whole generic set: url_key / url_path would change product URLs, the rest is layout XML,
     * image labels and model numbers.
     */
    public const TRANSLATABLE = ['name', 'short_description', 'description', 'meta_title', 'meta_keyword', 'meta_description'];

    private const PRODUCT_INDEXERS = ['catalogsearch_fulltext'];

    /** @var array<string, \Magento\Catalog\Api\Data\ProductAttributeInterface>|null */
    private ?array $attributes = null;

    public function __construct(
        private readonly VendorAccessGuard $guard,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductAttributeRepositoryInterface $attributeRepository,
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly VendorProductHelper $vendorProductHelper,
        private readonly UpdateFactory $updateFactory,
        private readonly Serialize $serializer,
        private readonly ProductAction $productAction,
        private readonly IndexerRegistry $indexerRegistry,
        private readonly CacheContextFactory $cacheContextFactory,
        private readonly EventManager $eventManager,
        private readonly AttributeValueFactory $attributeValueFactory,
        private readonly TranslationsInterfaceFactory $translationsFactory,
        private readonly StoreTranslationInterfaceFactory $storeTranslationFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function get($customerId, $sku)
    {
        $vendorId = (int) $this->guard->assertApprovedSeller((int) $customerId)->getId();

        return $this->build($this->ownProduct($vendorId, (string) $sku));
    }

    public function getForNewProduct($customerId)
    {
        $this->guard->assertApprovedSeller((int) $customerId);
        $websiteId = (int) $this->storeManager->getDefaultStoreView()->getWebsiteId();

        return $this->build(null, [$websiteId]);
    }

    public function save($customerId, $sku, array $translations)
    {
        $vendor = $this->guard->assertApprovedSeller((int) $customerId);
        $product = $this->ownProduct((int) $vendor->getId(), (string) $sku);
        $productId = (int) $product->getId();
        $stores = $this->storesFor($product->getWebsiteIds());
        $attributes = $this->translatable();

        $changes = [];   // [store_id => [attribute_code => string|null]]
        foreach ($translations as $translation) {
            $code = trim((string) $translation->getStoreCode());
            $store = $stores[$code] ?? null;
            if ($store === null) {
                throw new InputException(__(
                    'Unknown store view "%1". Use one of: %2.',
                    $code,
                    implode(', ', array_keys($stores))
                ));
            }
            $storeId = (int) $store->getId();
            $current = $this->values($productId, [$storeId])[$storeId] ?? [];
            foreach ($translation->getValues() as $value) {
                $attributeCode = trim((string) $value->getAttributeCode());
                if (!isset($attributes[$attributeCode])) {
                    throw new InputException(__(
                        '"%1" cannot be translated. Translatable fields: %2.',
                        $attributeCode,
                        implode(', ', array_keys($attributes))
                    ));
                }
                $new = $this->normalise($value->getValue());
                if ($new !== null && $attributes[$attributeCode]->getBackendType() === 'varchar' && mb_strlen($new) > 255) {
                    throw new InputException(__('"%1" can be at most 255 characters.', $attributeCode));
                }
                if ($new === ($current[$attributeCode] ?? null)) {
                    continue;   // unchanged, like ProductRepository::hmDropUnchanged()
                }
                $changes[$storeId][$attributeCode] = $new;
            }
        }

        if ($changes) {
            if ($this->needsReview($product)) {
                $this->queue($product, $vendor, $changes);
                //  Pending Update takes the product offline, as an app edit through update() does.
                $this->refresh($productId, array_map(fn ($s) => (int) $s->getId(), $stores));
            } else {
                $this->saveNow($productId, $changes, $attributes);
            }
        }

        return $this->build($this->productRepository->get((string) $sku, false, 0, true));
    }

    /**
     * The same test ProductRepository::update() makes before queueing an edit.
     */
    private function needsReview(ProductInterface $product): bool
    {
        if (!$this->vendorProductHelper->isUpdateProductsApproval()) {
            return false;
        }

        return !in_array(
            (int) $product->getData('approval'),
            [Approval::STATUS_PENDING, Approval::STATUS_NOT_SUBMITED, Approval::STATUS_UNAPPROVED],
            true
        );
    }

    /**
     * @param array<int, array<string, string|null>> $changes
     */
    private function queue(ProductInterface $product, $vendor, array $changes): void
    {
        $productId = (int) $product->getId();
        foreach ($changes as $storeId => $data) {
            /** @var Update $update */
            $update = $this->updateFactory->create()->getCollection()
                ->addFieldToFilter('vendor_id', (int) $vendor->getId())
                ->addFieldToFilter('store_id', $storeId)
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('status', Update::STATUS_PENDING)
                ->getFirstItem();
            //  update_id, not getId(): Vnecoms' Update model has no id field mapped (their own code always
            //  calls setId($update->getUpdateId()) before saving).
            if ($update->getData('update_id')) {
                $queued = $this->unserialize((string) $update->getData('product_data'));
                $update->setData('product_data', $this->serializer->serialize(array_merge($queued, $data)))
                    ->setId($update->getData('update_id'))
                    ->save();
            } else {
                $this->updateFactory->create()->setData([
                    'vendor_id' => (int) $vendor->getId(),
                    'store_id' => $storeId,
                    'product_id' => $productId,
                    'product_data' => $this->serializer->serialize($data),
                    'status' => Update::STATUS_PENDING,
                ])->save();
            }
        }

        /** @var Product $product */
        if ((int) $product->getData('approval') !== Approval::STATUS_PENDING_UPDATE) {
            $product->setData('approval', Approval::STATUS_PENDING_UPDATE)
                ->setStoreId(0)
                ->getResource()
                ->saveAttribute($product, 'approval');
        }
        try {
            $this->vendorProductHelper->sendUpdateProductApprovalEmailToAdmin($product, $vendor);
        } catch (\Throwable $e) {
            //  Mail on this host is unreliable; the queued update is what matters.
            $this->logger->warning('Vendor translations: approval email failed: ' . $e->getMessage());
        }
    }

    /**
     * @param array<int, array<string, string|null>> $changes
     * @param array<string, \Magento\Catalog\Api\Data\ProductAttributeInterface> $attributes
     */
    private function saveNow(int $productId, array $changes, array $attributes): void
    {
        $conn = $this->resource->getConnection();
        foreach ($changes as $storeId => $data) {
            $set = array_filter($data, static fn ($v) => $v !== null);
            if ($set) {
                $this->productAction->updateAttributes([$productId], $set, $storeId);
            }
            foreach (array_keys(array_diff_key($data, $set)) as $code) {
                $attribute = $attributes[$code];
                $conn->delete($attribute->getBackendTable(), [
                    'entity_id = ?' => $productId,
                    'attribute_id = ?' => (int) $attribute->getAttributeId(),
                    'store_id = ?' => $storeId,
                ]);
            }
        }
        $this->refresh($productId, array_keys($changes));
    }

    /**
     * Search index, page cache and Algolia for the product, as ProductApprovalGoLive does on approval.
     *
     * @param int[] $storeIds
     */
    private function refresh(int $productId, array $storeIds): void
    {
        try {
            foreach (self::PRODUCT_INDEXERS as $indexerId) {
                $this->indexerRegistry->get($indexerId)->reindexList([$productId]);
            }
            $context = $this->cacheContextFactory->create();
            $context->registerEntities(Product::CACHE_TAG, [$productId]);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $context]);

            if (class_exists(\Algolia\AlgoliaSearch\Helper\Data::class)) {
                $algolia = \Magento\Framework\App\ObjectManager::getInstance()->get(\Algolia\AlgoliaSearch\Helper\Data::class);
                foreach ($storeIds as $storeId) {
                    $algolia->rebuildStoreProductIndex((int) $storeId, [$productId]);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Vendor translations: refresh failed for product ' . $productId . ': ' . $e->getMessage());
        }
    }

    /**
     * @param int[]|null $websiteIds for a new product
     */
    private function build(?ProductInterface $product, ?array $websiteIds = null): TranslationsInterface
    {
        $attributes = $this->translatable();
        $stores = $this->storesFor($product ? $product->getWebsiteIds() : (array) $websiteIds);
        $values = $product
            ? $this->values((int) $product->getId(), array_merge([0], array_map(fn ($s) => (int) $s->getId(), $stores)))
            : [];

        $storeTranslations = [];
        foreach ($stores as $code => $store) {
            $storeTranslations[] = $this->storeTranslationFactory->create()
                ->setStoreId((int) $store->getId())
                ->setStoreCode($code)
                ->setStoreName((string) $store->getName())
                ->setLocale((string) $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $store->getId()))
                ->setValues($this->attributeValues($attributes, $values[(int) $store->getId()] ?? []));
        }

        return $this->translationsFactory->create()
            ->setDefaultValues($this->attributeValues($attributes, $values[0] ?? []))
            ->setStores($storeTranslations);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, string|null> $values
     * @return AttributeInterface[]
     */
    private function attributeValues(array $attributes, array $values): array
    {
        $out = [];
        foreach (array_keys($attributes) as $code) {
            $out[] = $this->attributeValueFactory->create()
                ->setAttributeCode($code)
                ->setValue($values[$code] ?? null);
        }

        return $out;
    }

    /**
     * Own values per store: [store_id => [attribute_code => value]]; a store without a row has no key.
     *
     * @param int[] $storeIds
     * @return array<int, array<string, string|null>>
     */
    private function values(int $productId, array $storeIds): array
    {
        $conn = $this->resource->getConnection();
        $out = [];
        foreach ($this->translatable() as $code => $attribute) {
            $rows = $conn->fetchPairs(
                $conn->select()
                    ->from($attribute->getBackendTable(), ['store_id', 'value'])
                    ->where('entity_id = ?', $productId)
                    ->where('attribute_id = ?', (int) $attribute->getAttributeId())
                    ->where('store_id IN (?)', $storeIds)
            );
            foreach ($rows as $storeId => $value) {
                $out[(int) $storeId][$code] = $value === null ? null : (string) $value;
            }
        }

        return $out;
    }

    /**
     * Active store views of the given websites, keyed by code, default store view of each website first.
     *
     * @param int[] $websiteIds
     * @return array<string, \Magento\Store\Api\Data\StoreInterface>
     */
    private function storesFor(array $websiteIds): array
    {
        $out = [];
        foreach (array_unique(array_map('intval', $websiteIds)) as $websiteId) {
            try {
                $website = $this->storeManager->getWebsite($websiteId);
            } catch (\Throwable $e) {
                continue;
            }
            $default = (int) $website->getDefaultStore()?->getId();
            $stores = array_filter($website->getStores(), static fn ($s) => (bool) $s->getIsActive());
            usort($stores, static fn ($a, $b) => [(int) $a->getId() !== $default, (int) $a->getSortOrder(), (int) $a->getId()]
                <=> [(int) $b->getId() !== $default, (int) $b->getSortOrder(), (int) $b->getId()]);
            foreach ($stores as $store) {
                $out[(string) $store->getCode()] = $store;
            }
        }

        return $out;
    }

    /**
     * @return array<string, \Magento\Catalog\Api\Data\ProductAttributeInterface>
     */
    private function translatable(): array
    {
        if ($this->attributes !== null) {
            return $this->attributes;
        }
        $notUsed = (array) $this->vendorProductHelper->getNotUsedVendorAttributes();
        $this->attributes = [];
        foreach (self::TRANSLATABLE as $code) {
            try {
                $attribute = $this->attributeRepository->get($code);
            } catch (NoSuchEntityException $e) {
                continue;
            }
            if ((int) $attribute->getIsGlobal() === ScopedAttributeInterface::SCOPE_STORE
                && in_array($attribute->getFrontendInput(), ['text', 'textarea'], true)
                && !in_array($code, $notUsed, true)
            ) {
                $this->attributes[$code] = $attribute;
            }
        }

        return $this->attributes;
    }

    /**
     * The caller's own product at store 0, or the same 404 as DELETE /V1/vendors/product/:sku.
     */
    private function ownProduct(int $vendorId, string $sku): ProductInterface
    {
        $notFound = new NoSuchEntityException(__('The product "%1" was not found among your products.', $sku));
        try {
            $product = $this->productRepository->get($sku, false, 0, true);
        } catch (NoSuchEntityException $e) {
            throw $notFound;
        }
        if ((int) $product->getData('vendor_id') !== $vendorId) {
            throw $notFound;
        }

        return $product;
    }

    private function normalise(mixed $value): ?string
    {
        if ($value === null || is_array($value)) {
            return null;
        }
        $value = (string) $value;

        return trim($value) === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function unserialize(string $data): array
    {
        if ($data === '') {
            return [];
        }
        try {
            $value = $this->serializer->unserialize($data);
        } catch (\Throwable $e) {
            return [];
        }

        return is_array($value) ? $value : [];
    }
}
