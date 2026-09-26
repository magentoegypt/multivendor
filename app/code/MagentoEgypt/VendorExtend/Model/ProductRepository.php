<?php
namespace MagentoEgypt\VendorExtend\Model;

use Vnecoms\VendorsApi\Model\ProductRepository as BaseProductRepository;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory as SearchResultFactory;
use Vnecoms\VendorsApi\Helper\Data as ApiHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Vnecoms\VendorsProduct\Model\Source\Approval;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class ProductRepository extends BaseProductRepository
{
    /**
     * @var int
     */
    private $cacheLimit = 0;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;
    
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * ProductRepository constructor.
     * @param ApiHelper $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Vnecoms\VendorsProduct\Helper\Data $vendorProductHelper
     * @param \Vnecoms\VendorsApi\Api\Data\Catalog\ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor
     * @param CollectionFactory $collectionFactory
     * @param int $cacheLimit
     */
    public function __construct(
        ApiHelper $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Vnecoms\VendorsProduct\Helper\Data $vendorProductHelper,
        \Vnecoms\VendorsApi\Api\Data\Catalog\ProductSearchResultsInterfaceFactory $searchResultsFactory,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor = null,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        $cacheLimit = 1000
    )
    {
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->cacheLimit = (int)$cacheLimit;

        parent::__construct(
            $helper,
            $objectManager,
            $vendorProductHelper,
            $searchResultsFactory,
            $productRepository,
            $extensionAttributesJoinProcessor,
            $collectionProcessor,
            $serializer,
            $logger,
            $productPriceIndexerProcessor,
            $collectionFactory,
            $cacheLimit
        );
    }

    /**
     * POST /V1/vendors/product/save — refuses a SKU (or product id) that belongs to someone else.
     *
     * Vnecoms' own "already exists" guard throws its LocalizedException INSIDE the
     * try whose catch(\Exception) swallows it, so it never fired. Core's repository then
     * treats a known SKU as an update: posting another seller's (or the admin's) SKU
     * rewrote that product and moved it to the caller (vendor app ticket 86d4b10r8,
     * which became reachable once vendors could type their own SKU).
     *
     * A SKU the caller already owns still goes through, as it always has.
     *
     * @inheritdoc
     */
    public function save(
        $customerId,
        \Magento\Catalog\Api\Data\ProductInterface $product,
        $saveOptions = false,
        $saveDraft = false,
        $storeId = null
    ) {
        $vendorId = (int) $this->helper->getVendorByCustomerId($customerId)->getId();
        $sku = trim((string) $product->getSku());
        if ($sku === '') {
            throw new \Magento\Framework\Exception\InputException(__('SKU is required.'));
        }

        /* Exact SKU lookup — NOT $this->get(), which reads a numeric SKU as an entity id. */
        $productResource = $this->objectManager->get(\Magento\Catalog\Model\ResourceModel\Product::class);
        $skuOwnerId = (int) $productResource->getIdBySku($sku);
        if ($skuOwnerId && $this->getProductVendorId($skuOwnerId) !== $vendorId) {
            throw new LocalizedException(
                __('The SKU "%1" is already used by another product. Please choose a different SKU.', $sku)
            );
        }

        $productId = (int) $product->getId();
        if ($productId) {
            if ($this->getProductVendorId($productId) !== $vendorId
                || ($skuOwnerId && $skuOwnerId !== $productId)
            ) {
                throw new LocalizedException(__('You are not permitted to save product %1', $sku));
            }
        }

        return parent::save($customerId, $product, $saveOptions, $saveDraft, $storeId);
    }

    /**
     * DELETE /V1/vendors/product/:sku — only the owning seller (or an admin/integration).
     *
     * The route forces customerId, but the interface method takes only the SKU, so
     * Vnecoms deleted whatever SKU it was given: any seller could delete any product.
     * The caller is read from the Web API user context instead.
     *
     * @inheritdoc
     */
    public function deleteById($sku)
    {
        $userContext = $this->objectManager->get(\Magento\Authorization\Model\UserContextInterface::class);
        $userType = (int) $userContext->getUserType();

        if ($userType === \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
            $vendorId = (int) $this->helper->getVendorByCustomerId($userContext->getUserId())->getId();
            $product = $this->productRepository->get($sku);
            if ((int) $product->getVendorId() !== $vendorId) {
                throw new LocalizedException(__('You are not permitted to delete product %1', $sku));
            }
        } elseif ($userType !== \Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN
            && $userType !== \Magento\Authorization\Model\UserContextInterface::USER_TYPE_INTEGRATION
        ) {
            throw new \Magento\Framework\Exception\AuthorizationException(__('You are not permitted to delete products.'));
        }

        return parent::deleteById($sku);
    }

    /**
     * vendor_id of a product (0 = admin-owned), read from the catalog, not the request.
     */
    private function getProductVendorId(int $productId): int
    {
        return (int) $this->productRepository->getById($productId)->getVendorId();
    }

    /**
     * @param int $customerId
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string[] $attributes
     * @param string $saveDraft
     * @param string $storeId
     * @throws LocalizedException
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function update(
        $customerId,
        \Magento\Catalog\Api\Data\ProductInterface $product,
        $attributes,
        $saveDraft = false,
        $storeId=null
    ){
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();

        /*
         * A seller never sets `approval` or `vendor_id` (TC24-QA02). Vnecoms lists them as not
         * vendor-editable, and _getChangedData() skips them, but the "save data which is not
         * required for approval" loop below copied every listed attribute that was NOT held for
         * review, i.e. exactly these. App builds that send the approval dropdown therefore left an
         * edited, approved product at the app's value (Approved) instead of Pending Update, and
         * the edit sat in the update queue unseen. Drop them from the list before anything runs.
         */
        $attributes = array_values(array_diff(
            (array) $attributes,
            $this->vendorProductHelper->getNotUsedVendorAttributes()
        ));

        $om = $this->objectManager;
        if(in_array('sku', $attributes)){
            $existProduct = $this->getById($product->getId());
        } else {
            $existProduct = $this->get($product->getSku());
        }
        if($existProduct->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not permited to save product %1', $product->getSku()));
        }
        $existProduct = $om->create('Magento\Catalog\Model\Product')->load($existProduct->getId());

        $saveProductFlag = false;
        $changedData = $this->_getChangedData($product, $existProduct, $attributes);
        if ($this->vendorProductHelper->isUpdateProductsApproval()) {
            if (!in_array($existProduct->getApproval(), [Approval::STATUS_PENDING, Approval::STATUS_NOT_SUBMITED, Approval::STATUS_UNAPPROVED])) {
                if (sizeof($changedData)) {
                    /*Save changed data*/
                    $update = $om->create('Vnecoms\VendorsProduct\Model\Product\Update');

                    /*Check if there is an exist pending update*/
                    $collection = $update->getCollection()
                        ->addFieldToFilter('vendor_id', $vendorId)
                        ->addFieldToFilter('store_id', $storeId)
                        ->addFieldToFilter('product_id', $existProduct->getId())
                        ->addFieldToFilter('status', \Vnecoms\VendorsProduct\Model\Product\Update::STATUS_PENDING);
                    if ($collection->count()) {
                        /*Update changed data*/
                        $update = $collection->getFirstItem();
                        $update->setProductData(serialize($changedData));
                        $update->setId($update->getUpdateId())->save();
                    } else {
                        $update->setData([
                            'vendor_id' => $vendorId,
                            'store_id' => $storeId,
                            'product_id' => $existProduct->getId(),
                            'product_data' => serialize($changedData),
                            'status' => \Vnecoms\VendorsProduct\Model\Product\Update::STATUS_PENDING
                        ])->save();
                    }

                    if (!$saveDraft) {
                        /* $existProduct, not the request's $product: that one carries the app's value */
                        $existProduct->setApproval(Approval::STATUS_PENDING_UPDATE)
                            ->getResource()
                            ->saveAttribute($existProduct, 'approval');
                        $this->vendorProductHelper->sendUpdateProductApprovalEmailToAdmin($existProduct, $vendor);
                    }
                    
                    /*Save data which is not required for approval*/
                    foreach($attributes as $attr){
                        if($attr == 'media_gallery_entries') {
                            $existProduct->setMediaGalleryEntries($product->getMediaGalleryEntries());
                            continue;
                        }
                        if(isset($changedData[$attr])) continue;
                        $existProduct->setData($attr, $product->getData($attr));
                    }
                    $this->productRepository->save($existProduct);
                    // $existProduct->save();
                }
            } else {
                $saveProductFlag = true;
                if (!$saveDraft) {
                    if ($existProduct->getApproval() != Approval::STATUS_PENDING) {
                        $this->vendorProductHelper->sendUpdateProductApprovalEmailToAdmin($existProduct, $vendor);
                    }

                    $existProduct->setApproval(Approval::STATUS_PENDING)
                        ->getResource()
                        ->saveAttribute($existProduct, 'approval');
                }
            }
        } else {
            $saveProductFlag = true;
            if ($product->getApproval() == Approval::STATUS_PENDING_UPDATE) {
                $product->setApproval(Approval::STATUS_APPROVED);
            }
        }
        if($saveProductFlag){
            foreach($changedData as $attr=>$value){
                if($attr == 'media_gallery_entries') {
                    $existProduct->setMediaGalleryEntries($product->getMediaGalleryEntries());
                } else {
                    $existProduct->setData($attr, $value);
                }
            }
            $this->productRepository->save($existProduct);
            // $existProduct->save();
        }
        if(in_array('stock_item', $attributes)){
            $this->saveStockItem($product, $product->getExtensionAttributes()->getStockItem()->getData());
        }
        return $this->getById($product->getId());
    }

    /**
     * Get changed data
     * 
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param \Magento\Catalog\Api\Data\ProductInterface $oldProduct
     * @param string[] $attributes
     * @return array
     */
    private function _getChangedData(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        \Magento\Catalog\Api\Data\ProductInterface $oldProduct,
        $attributes
    ) {
        $changedData = [];
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $productAttrCollection = $om->create('Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection')
            ->addVisibleFilter();
        $notUsedProductAttr = $this->vendorProductHelper->getNotUsedVendorAttributes();
        $updateApprovalFlag = $this->vendorProductHelper->getUpdateProductsApprovalFlag();
        $approvalAttrs      = $this->vendorProductHelper->getUpdateProductsApprovalAttributes();
        
        foreach ($attributes as $attrCode) {
            if (in_array($attrCode, $notUsedProductAttr)) {
                continue;
            }
            if($updateApprovalFlag && !in_array($attrCode, $approvalAttrs)) continue;
            if(!$updateApprovalFlag && in_array($attrCode, $approvalAttrs)) continue;

            $newData = $product->getData($attrCode);

            $changedData[$attrCode] = $newData;
        }
        return $changedData;
    }

    /**
     * Save stock item for the product
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $stockData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function saveStockItem($product, array $stockData)
    {
        $stockRegistry = $this->objectManager->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class);
        $stockItem = $stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        
        foreach ($stockData as $key => $value) {
            $stockItem->setData($key, $value);
        }
        
        $stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
    }

    /**
     * @param string $sku
     * @param bool $editMode
     * @param null $storeId
     * @param bool $forceReload
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($sku, $editMode = false, $storeId = null, $forceReload = false)
    {
        if(is_numeric($sku))
        {
            $product = $this->getById($sku, $editMode, $storeId, $forceReload);
            if($product->getId()) return $product;
        }
        return parent::get($sku, $editMode, $storeId, $forceReload);
    }
}