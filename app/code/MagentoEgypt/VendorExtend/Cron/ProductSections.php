<?php
namespace MagentoEgypt\VendorExtend\Cron;

use Vnecoms\VendorsProduct\Model\Source\Approval as ProductApproval;

class ProductSections
{
	const LIMIT = 10;
	const RELATED = 'related';
	const UPSELL = 'upsell';
	const CROSSSELL = 'crosssell';

	protected $product;
	protected $productLinks = [];
	protected $linkedSkus = [];
	protected $rootCats = [];
	protected $storeId = null;

	protected $productCollectionFactory;
	protected $productRepository;
	protected $productVisibility;
	protected $productStatus;
	protected $productLinkFactory;
	protected $storeManager;

	public function __construct(
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
		\Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
    	\Magento\Catalog\Model\Product\Visibility $productVisibility,
		\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
	)
	{
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productRepository = $productRepository;
		$this->productStatus = $productStatus;
    	$this->productVisibility = $productVisibility;
		$this->productLinkFactory = $productLinkFactory;
		$this->storeManager = $storeManager;
	}

	/**
	 * Resolve a valid storefront store id. In a cron context the current store
	 * defaults to the admin store (0), which has no catalog_category_product_index_store0
	 * table and makes every storefront product collection query fail. Bind the
	 * collections to a real store view instead.
	 */
	protected function resolveStoreId()
	{
		$store = $this->storeManager->getDefaultStoreView();
		if (!$store) {
			foreach ($this->storeManager->getStores() as $candidate) {
				$store = $candidate;
				break;
			}
		}
		return $store ? (int) $store->getId() : \Magento\Store\Model\Store::DEFAULT_STORE_ID;
	}

	public function execute()
	{
		$this->storeId = $this->resolveStoreId();

		$collection = $this->productCollectionFactory->create();
		$collection->setStoreId($this->storeId);
		$collection->addAttributeToFilter('sku', 'test item');
		$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
    	$collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
		$collection->addFinalPrice();

    	foreach($collection as $entity)
    	{
    		$this->product = $this->productRepository->get($entity->getSku());
    		$this->product->setProductLinks(
    			$this->addRelated()->addUpsell()->addCrossSell()
    		);
    		try {
    			$this->product->getLinkInstance()->saveProductRelations($this->product);
    		} catch(\Exception $e) {  }
    	}
	}

	protected function addRelated($isNotVendor = 0)
	{
		$this->productLinks = [];
		$this->linkedSkus = [];

		$collection = $this->productCollectionFactory->create();
		$collection->setStoreId($this->storeId);
		$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
		$collection->addAttributeToFilter('approval', ProductApproval::STATUS_APPROVED);
    	$collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
    	if($isNotVendor) {
	    	$collection->addCategoriesFilter(['in' => $this->getCategoryIds()]);
	    	$collection->setPageSize(self::LIMIT-$isNotVendor);
    	} else {
    		$collection->setPageSize(self::LIMIT);
    	}
    	$collection->getSelect()->orderRand();

    	$i = 0;
    	foreach($collection as $linkProduct) {
    		$sku = $linkProduct->getSku();
    		$link = $this->productLinkFactory->create();
            $link->setSku($this->product->getSku())
                ->setLinkedProductSku($sku)
                ->setLinkType(self::RELATED)
                ->setPosition($i++);
            $this->productLinks[] = $link;
            $this->linkedSkus[] = $sku;
    	}

    	return (!$isNotVendor && $i < self::LIMIT) ? $this->addUpsell($i) : $this;
	}

	protected function addUpsell($isNotVendor = 0)
	{
		$collection = $this->productCollectionFactory->create();
		$collection->setStoreId($this->storeId);
		$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
		$collection->addAttributeToFilter('approval', ProductApproval::STATUS_APPROVED);
    	$collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
		$collection->addAttributeToFilter('sku', ['nin' => $this->linkedSkus]);
    	$collection->addPriceDataFieldFilter('%s <= %s', ['final_price', $this->product->getFinalPrice()]);
    	$collection->addCategoriesFilter(['in' => $this->getCategoryIds()]);
    	$collection->setPageSize(self::LIMIT);
    	$collection->getSelect()->orderRand();

    	$i = 0;
    	foreach($collection as $linkProduct) {
    		$sku = $linkProduct->getSku();
    		$link = $this->productLinkFactory->create();
            $link->setSku($this->product->getSku())
                ->setLinkedProductSku($sku)
                ->setLinkType(self::UPSELL)
                ->setPosition($i++);
            $this->productLinks[] = $link;
            $this->linkedSkus[] = $sku;
    	}

    	return (!$isNotVendor && $i < self::LIMIT) ? $this->addUpsell($i) : $this;
	}

	protected function addCrossSell($isNotVendor = 0)
	{
		$collection = $this->productCollectionFactory->create();
		$collection->setStoreId($this->storeId);
		$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
		$collection->addAttributeToFilter('approval', ProductApproval::STATUS_APPROVED);
    	$collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
		$collection->addCategoriesFilter(['nin' => $this->getCategoryIds()]);
    	$collection->addPriceDataFieldFilter('%s <= %s', ['final_price', $this->product->getFinalPrice()]);
    	$collection->setPageSize(self::LIMIT);
    	$collection->getSelect()->orderRand();

    	$i = 0;
    	foreach($collection as $linkProduct) {
    		$sku = $linkProduct->getSku();
    		$link = $this->productLinkFactory->create();
            $link->setSku($this->product->getSku())
                ->setLinkedProductSku($sku)
                ->setLinkType(self::CROSSSELL)
                ->setPosition($i++);
            $this->productLinks[] = $link;
            $this->linkedSkus[] = $sku;
    	}

    	return $this->productLinks;
	}

	public function getCategoryIds()
	{
		if(count($this->rootCats)==0) {
			$ids = [\Magento\Catalog\Model\Category::TREE_ROOT_ID];
	        foreach ($this->storeManager->getGroups() as $store) {
	            $ids[] = $store->getRootCategoryId();
	        }
	        $this->rootCats = $ids;
		}
	    $currentCids = $this->product->getCategoryIds();
		return array_diff($currentCids ?? [], $this->rootCats);
	}
}
