<?php
namespace Vnecoms\VendorsPriceComparison\Model;

use Vnecoms\VendorsPriceComparison\Model\Source\Product\Main;
use Vnecoms\VendorsProduct\Helper\Data as ProductHelper;
use Vnecoms\VendorsPriceComparison\Helper\Data as PriceComparisonHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Process
{
    /**
     * @var \Vnecoms\VendorsPriceComparison\Helper\Data
     */
    protected $_priceComparisonHelper;

    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $_productHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var array
     */
    protected $_copyAttributes;

    /**
     * @var boolean
     */
    protected $copyFlag = true;

    /**
     * @var ProductUrlRewriteGenerator
     */
    protected $productUrlRewriteGenerator;

    /**
     * @var UrlPersistInterface
     */
    protected $urlPersist;

    /**
     * Vendor helper
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $vendorHelper;

    /**
     * @var Visibility
     */
    protected $productVisibility;

    /**
     * @var array
     */
    protected $notActiveVendorIds = [];

    /**
     * @var array
     */
    protected $statusApproval = [];

    /**
     * Application Event Dispatcher
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Vnecoms\VendorsPriceComparison\Model\QueueFactory
     */
    protected $queue;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockItemRepository;

    /**
     * Process constructor.
     * @param ProductHelper $productHelper
     * @param PriceComparisonHelper $priceComparisonHelper
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param ProductUrlRewriteGenerator $productUrlRewriteGenerator
     * @param UrlPersistInterface $urlPersist
     * @param \Vnecoms\Vendors\Helper\Data $helper
     * @param Visibility $productVisibility
     * @param \Magento\Framework\Event\ManagerInterface $eventManagerInterface
     * @param QueueFactory $queue
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockItemRepository
     */
    public function __construct(
        ProductHelper $productHelper,
        PriceComparisonHelper $priceComparisonHelper,
        CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        ProductUrlRewriteGenerator $productUrlRewriteGenerator,
        UrlPersistInterface $urlPersist,
        \Vnecoms\Vendors\Helper\Data $helper,
        Visibility $productVisibility,
        \Magento\Framework\Event\ManagerInterface $eventManagerInterface,
        \Vnecoms\VendorsPriceComparison\Model\QueueFactory $queue,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockItemRepository
    ) {
        $this->_priceComparisonHelper   = $priceComparisonHelper;
        $this->_productHelper           = $productHelper;
        $this->_collectionFactory       = $collectionFactory;
        $this->productFactory           = $productFactory;
        $this->urlPersist = $urlPersist;
        $this->productUrlRewriteGenerator = $productUrlRewriteGenerator;
        $this->vendorHelper = $helper;
        $this->productVisibility = $productVisibility;
        $this->stockItemRepository = $stockItemRepository;
        $this->_eventManager = $eventManagerInterface;
        $this->notActiveVendorIds = $this->vendorHelper->getNotActiveVendorIds();
        $this->statusApproval = $this->_productHelper->getAllowedApprovalStatus();
        $this->queue = $queue;
    }

    /**
     * @param $productId
     * @return bool|void|null
     */
    public function deleteMainProduct($productId){
        $collection = $this->_collectionFactory->create()
            ->addAttributeToSelect("*")
            ->addAttributeToFilter('select_from_product_id', $productId);

        if(!$collection->count()) return;

        if($this->_priceComparisonHelper->getMainProductType() == Main::TYPE_FIRST_VENDOR){
            return $this->updateFirstVendorProduct($collection);
        }

        if($this->_priceComparisonHelper->getMainProductType() == Main::TYPE_LOWEST_PRICE){
            return $this->updateLowestPriceVendorProduct($collection);
        }
    }

    /**
     * Process main product
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function updateMainProduct($mainProductId){
        $collection = $this->_collectionFactory->create()->addAttributeToSelect("*");
        $collection->addAttributeToFilter('select_from_product_id', $mainProductId);
        if(!$collection->count()) return;
        $mainProduct = $this->_collectionFactory->create()->addAttributeToSelect("*")
            ->addAttributeToFilter("entity_id", $mainProductId)->getFirstItem();
        $oldMainProduct = $mainProduct;
        if (!$mainProduct->getId()) return;

        $isStockCheck = $mainProduct->getTypeId() == Configurable::TYPE_CODE ? false : true;

        if (!$this->checkProductSalesEnable($mainProduct, true , $isStockCheck)) {
            if($this->_priceComparisonHelper->getMainProductType() == Main::TYPE_FIRST_VENDOR){
                $mainProduct = $this->updateFirstVendorProduct($collection, $mainProduct);
            }

            if($this->_priceComparisonHelper->getMainProductType() == Main::TYPE_LOWEST_PRICE){
                $mainProduct = $this->updateLowestPriceVendorProduct($collection, $mainProduct);
            }
        } else {
            if ($this->_priceComparisonHelper->getMainProductType() == Main::TYPE_LOWEST_PRICE)  {
                $oldMainProduct = $mainProduct;
                $mainProductPrice = $mainProduct->getPrice();

                if ($mainProduct->getTypeId() == Configurable::TYPE_CODE) {
                    $childProducts = $mainProduct->getTypeInstance()->getUsedProducts($mainProduct);
                    if ($childProducts) {
                        foreach ($childProducts as $childs) {
                            if (!$this->checkProductSalesEnable($childs, false)) continue;
                            $productPrice = $childs->getPrice();
                            $mainProductPrice = $mainProductPrice ? min($mainProductPrice, $productPrice) : $productPrice;
                        }
                    }
                }

                /* Find lowest price product (Main Product)*/
                foreach($collection as $p){
                    $isStockCheck = $p->getTypeId() == Configurable::TYPE_CODE ? false : true;
                    if (!$this->checkProductSalesEnable($p, true, $isStockCheck)) continue;
                    $newPrice = $p->getPrice();
                    if ($p->getTypeId() == Configurable::TYPE_CODE) {
                        $newChildProducts = $p->getTypeInstance()->getUsedProducts($p);
                        if ($newChildProducts) {
                            foreach ($newChildProducts as $childs) {
                                if (!$this->checkProductSalesEnable($childs, false)) continue;
                                $productPrice = $childs->getPrice();
                                $newPrice = $newPrice ? min($newPrice, $productPrice) : $productPrice;
                            }
                        }
                    }

                    if($mainProductPrice > $newPrice){
                        $mainProduct = $p;
                    }
                }

                if($mainProduct->getId() == $oldMainProduct->getId()) return;

                $this->setSelectFromProductId($mainProduct, 0);
                foreach($collection as $p){
                    if($p->getId() == $mainProduct->getId()) continue;
                    $this->setSelectFromProductId($p, $mainProduct->getId());
                }
                $this->setSelectFromProductId($oldMainProduct, $mainProduct->getId());
            }
        }
        $oldMainProduct->cleanCache();
        $this->_eventManager->dispatch('clean_cache_by_tags', ['object' => $oldMainProduct]);
        return $mainProduct;
    }

    /**
     * @param $product
     * @param $urlKey
     * @throws \Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException
     */
    protected function updateUrlKeyProduct($product, $urlKey) {
        $this->setUrlKeyProduct($product, $urlKey);
        $product->setData("save_rewrites_history", true);
        $this->urlPersist->replace($this->productUrlRewriteGenerator->generate($product));
    }

    /**
     * @param ProductCollection $collection
     * @param null $currentProduct
     */
    protected function updateLowestPriceVendorProduct(ProductCollection $collection, $currentProduct = null){
        $mainProduct = null;

        /* Find lowest price product*/
        foreach($collection as $product){
            $isStockCheck = $product->getTypeId() == Configurable::TYPE_CODE ? false : true;
            if (!$mainProduct && $this->checkProductSalesEnable($product, true, $isStockCheck)) {
                $mainProduct = $product;
                continue;
            }

            if ($mainProduct) {
                $mainProductPrice = $mainProduct->getPrice();
                if ($mainProduct->getTypeId() == Configurable::TYPE_CODE) {
                    $childProducts = $mainProduct->getTypeInstance()->getUsedProducts($mainProduct);
                    if ($childProducts) {
                        foreach ($childProducts as $childs) {
                            if (!$this->checkProductSalesEnable($childs, false)) continue;
                            $productPrice = $childs->getPrice();
                            $mainProductPrice = $mainProductPrice ? min($mainProductPrice, $productPrice) : $productPrice;
                        }
                    }
                }

                $newPrice = $product->getPrice();

                if ($product->getTypeId() == Configurable::TYPE_CODE) {
                    $newChildProducts = $product->getTypeInstance()->getUsedProducts($product);
                    if ($newChildProducts) {
                        foreach ($newChildProducts as $childs) {
                            if (!$this->checkProductSalesEnable($childs, false)) continue;
                            $productPrice = $childs->getPrice();
                            $newPrice = $newPrice ? min($newPrice, $productPrice) : $productPrice;
                        }
                    }
                }

                if($mainProductPrice > $newPrice) {
                    $mainProduct = $product;
                    continue;
                }
            }

        }

        if ($mainProduct) {
            $this->setSelectFromProductId($mainProduct, 0);

            foreach($collection as $product){
                if($product->getId() == $mainProduct->getId()) continue;
                $this->setSelectFromProductId($product, $mainProduct->getId());
            }

            if ($currentProduct) {
                $this->setSelectFromProductId($currentProduct, $mainProduct->getId());
                $currentProduct->cleanCache();
                $this->_eventManager->dispatch('clean_cache_by_tags', ['object' => $currentProduct]);
            }

            $mainProduct->cleanCache();
            $this->_eventManager->dispatch('clean_cache_by_tags', ['object' => $mainProduct]);
            return $mainProduct;
        }
        return false;
    }

    /**
     * Update main product is first vendor who create the product
     *
     * @param ProductCollection $collection
     */
    protected function updateFirstVendorProduct(ProductCollection $collection, $currentProduct = null){
        $mainProduct = null;
        foreach($collection as $product){
            $isStockCheck = $product->getTypeId() == Configurable::TYPE_CODE ? false : true;
            if(!$mainProduct && $this->checkProductSalesEnable($product, true, $isStockCheck)){
                $mainProduct = $product;
                $this->setSelectFromProductId($mainProduct, 0);
                continue;
            }

            if ($mainProduct)
            $this->setSelectFromProductId($product, $mainProduct->getId());
        }

        if ($currentProduct && $mainProduct) {
            $this->setSelectFromProductId($currentProduct, $mainProduct->getId());
        }
        $mainProduct->cleanCache();
        $this->_eventManager->dispatch('clean_cache_by_tags', ['object' => $mainProduct]);
        return $mainProduct;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function checkProductSalesEnable(
        \Magento\Catalog\Model\Product $product,
        $isVisibility = true,
        $isStock = true
    ) {
        if (
            in_array($product->getVendorId(), $this->notActiveVendorIds) ||
            !in_array($product->getApproval(), $this->statusApproval) ||
            !$product->isSaleable()
        ) return false;

        if ($isVisibility && $product->getVisibility() && !in_array($product->getVisibility(), $this->productVisibility->getVisibleInSearchIds())) {
            return false;
        }

        if ($isStock) {
            try {
                $checkStock = false;
                foreach ($product->getWebsiteIds() as $websiteId) {
                    $stock = $this->stockItemRepository->getStockStatus($product->getId(), $websiteId);
                    if ($stock->getQty() && $stock->getStockStatus()) {
                        $checkStock  = true;
                    }
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return $checkStock;
    }

    /**
     * set vale for select_from_product_id attribute
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int|null $value
     */
    protected function setSelectFromProductId(\Magento\Catalog\Model\Product $product, $value){
        $product->setData('select_from_product_id', $value)
            ->getResource()
            ->saveAttribute($product, 'select_from_product_id');
    }

    /**
     * set vale for select_from_product_id attribute
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int|null $value
     */
    protected function setUrlKeyProduct(\Magento\Catalog\Model\Product $product, $value){
        $product->setData('url_key', $value)
            ->getResource()
            ->saveAttribute($product, 'url_key');
    }

    /**
     * @param $productId
     * @param $type
     */
    public function saveQueueByProductIdType($productId, $type) {
        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToFilter('select_from_product_id', $productId);
        if($collection->count()) {
            $queues = $this->queue->create()->getCollection()
                ->addFieldToFilter("type", "update")
                ->addFieldToFilter("sell_product_id", $productId);
            if(!$queues->count()) {
                $this->queue->create()->setData(
                    [
                        "sell_product_id" => $productId,
                        "type" => $type
                    ]
                )->save();
            }
        }
    }
}
