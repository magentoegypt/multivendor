<?php

namespace Vnecoms\VendorsCategory\Model;

use Vnecoms\VendorsCategory\Api\Data\CategoryInterface;
use Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterface;
use Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface;
use Magento\Framework\Profiler;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\Framework\Exception\NoSuchEntityException;
use Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlPathGenerator;
use Vnecoms\VendorsCategory\Model\Rewrite\ChildrenCategoriesProvider;
/**
 * Class Category.
 *
 * @method Category setAffectedProductIds(array $productIds)
 * @method array getAffectedProductIds()
 * @method Category setMovedCategoryId(array $productIds)
 * @method int getMovedCategoryId()
 * @method Category setAffectedCategoryIds(array $categoryIds)
 * @method array getAffectedCategoryIds()
 * @method Category setUrlKey(string $urlKey)
 * @method Category setUrlPath(string $urlPath)
 * @method Category getSkipDeleteChildren()
 * @method Category setSkipDeleteChildren(boolean $value)
 */
class Category extends \Magento\Framework\Model\AbstractModel implements CategoryInterface, \Magento\Framework\DataObject\IdentityInterface, CategoryTreeInterface
{
    const CACHE_TAG = 'ves_vendorscategory_category';
    const KEY_PARENT_ID = 'parent_id';
    const KEY_NAME = 'name';
    const KEY_DESC = 'description';
    const KEY_VENDOR_ID = 'vendor_id';
    const KEY_IS_ACTIVE = 'is_active';
    const KEY_POSITION = 'position';
    const KEY_LEVEL = 'level';
    const KEY_UPDATED_AT = 'updated_at';
    const KEY_CREATED_AT = 'created_at';
    const KEY_PATH = 'path';
    const KEY_AVAILABLE_SORT_BY = 'available_sort_by';
    const KEY_INCLUDE_IN_MENU = 'include_in_menu';
    const KEY_PRODUCT_COUNT = 'product_count';
    const KEY_CHILDREN_DATA = 'children_data';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'vendors_category';

    /**
     * Parameter name in event.
     *
     * @var string
     */
    protected $_eventObject = 'category';

    /**
     * @var \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var ResourceModel\Category\Tree
     */
    protected $_treeModel;

    /**
     * @var ResourceModel\Category\TreeFactory
     */
    protected $_categoryTreeFactory;

    /**
     * @var \Vnecoms\VendorsCategory\Helper\Data
     */
    protected $helper;

    /** @var UrlFinderInterface */
    protected $urlFinder;

    /**
     * Core data.
     *
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $filter;

    /**
     * URL Model instance.
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor;

    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $vendorHelper;

    /** @var CategoryUrlPathGenerator */
    protected $categoryUrlPathGenerator;

    /**
     * Product collection factory.
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /** @var \Vnecoms\VendorsCategory\Model\Rewrite\ChildrenCategoriesProvider */
    protected $childrenCategoriesProvider;


    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsCategory\Model\ResourceModel\Category');
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree $categoryTreeResource,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\TreeFactory $categoryTreeFactory,
        CategoryRepositoryInterface $categoryRepository,
        \Vnecoms\VendorsCategory\Helper\Data $helper,
        UrlFinderInterface $urlFinder,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Filter\FilterManager $filter,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Vnecoms\VendorsPage\Helper\Data $vendorHelper,
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        ChildrenCategoriesProvider $childrenCategoriesProvider,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->categoryRepository = $categoryRepository;
        $this->_categoryTreeFactory = $categoryTreeFactory;
        $this->_treeModel = $categoryTreeResource;
        $this->helper = $helper;
        $this->_url = $url;
        $this->filter = $filter;
        $this->urlFinder = $urlFinder;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->vendorHelper = $vendorHelper;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->childrenCategoriesProvider = $childrenCategoriesProvider;
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    /**
     * Get parent category object.
     *
     * @return \Vnecoms\VendorsCategory\Model\Category
     */
    public function getParentCategory()
    {
        if (!$this->hasData('parent_category')) {
            $this->setData('parent_category', $this->categoryRepository->get($this->getParentId()));
        }

        return $this->_getData('parent_category');
    }

    /**
     * Get parent category identifier.
     *
     * @return int
     */
    public function getParentId()
    {
        $parentId = $this->getData(self::KEY_PARENT_ID);
        if (isset($parentId)) {
            return $parentId;
        }
        $parentIds = $this->getParentIds();

        return intval(array_pop($parentIds));
    }

    /**
     * Get all parent categories ids.
     *
     * @return array
     */
    public function getParentIds()
    {
        return array_diff($this->getPathIds(), [$this->getId()]);
    }

    /**
     * Get array categories ids which are part of category path
     * Result array contain id of current category because it is part of the path.
     *
     * @return array
     */
    public function getPathIds()
    {
        $ids = $this->getData('path_ids');
        if ($ids === null) {
            $ids = explode('/', $this->getPath());
            $this->setData('path_ids', $ids);
        }

        return $ids;
    }

    /**
     * Get Category Image URL.
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getImageUrl()
    {
        $url = false;
        $image = $this->getImage();
        if ($image) {
            if (is_string($image)) {
                $url = $this->helper->getBaseUrlMedia($image);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while getting the image url.')
                );
            }
        }

        return $url;
    }

    /**
     * Retrieve array of product id's for category.
     *
     * @return array
     */
    public function getProductsPosition()
    {
        if (!$this->getId()) {
            return [];
        }

        $array = $this->getData('products_position');
        if ($array === null) {
            $array = $this->getResource()->getProductsPosition($this);
            $this->setData('products_position', $array);
        }

        return $array;
    }

    /**
     * Move category.
     *
     * @param int      $parentId        new parent category id
     * @param null|int $afterCategoryId category id after which we have put current category
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function move($parentId, $afterCategoryId)
    {
        /*
         * Validate new parent category id. (category model is used for backward
         * compatibility in event params)
         */
        try {
            $parent = $this->categoryRepository->get($parentId);
        } catch (NoSuchEntityException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Sorry, but we can\'t find the new parent category you selected.'
                ),
                $e
            );
        }

        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Sorry, but we can\'t find the new category you selected.')
            );
        } elseif ($parent->getId() == $this->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'We can\'t move the category because the parent category name matches the child category name.'
                )
            );
        }

        /*
         * Setting affected category ids for third party engine index refresh
         */
        $this->setMovedCategoryId($this->getId());
        $oldParentId = $this->getParentId();
        $oldParentIds = $this->getParentIds();

        $eventParams = [
            $this->_eventObject => $this,
            'parent' => $parent,
            'category_id' => $this->getId(),
            'prev_parent_id' => $oldParentId,
            'parent_id' => $parentId,
        ];

        $this->_getResource()->beginTransaction();
        try {
            $this->_eventManager->dispatch($this->_eventPrefix.'_move_before', $eventParams);
            $this->getResource()->changeParent($this, $parent, $afterCategoryId);
            $this->_eventManager->dispatch($this->_eventPrefix.'_move_after', $eventParams);
            $this->_getResource()->commit();

            // Set data for indexer
            $this->setAffectedCategoryIds([$this->getId(), $oldParentId, $parentId]);
        } catch (\Exception $e) {
            $this->_getResource()->rollBack();
            throw $e;
        }
        $this->_eventManager->dispatch('vendor_category_move', $eventParams);

        //reindex product, under development

        $this->_eventManager->dispatch('clean_cache_by_tags', ['object' => $this]);
        $this->_cacheManager->clean([self::CACHE_TAG]);

        return $this;
    }

    /**
     * Retrieve category id URL.
     *
     * @return string
     */
    public function getCategoryIdUrl()
    {
        Profiler::start('REGULAR: '.__METHOD__, ['group' => 'REGULAR', 'method' => __METHOD__]);
        $urlKey = $this->getUrlKey() ? $this->getUrlKey() : $this->formatUrlKey($this->getName());

        $url = $this->vendorHelper->getUrl($this->getVendor(), $urlKey, ['id' => $this->getId()]);
        Profiler::stop('REGULAR: '.__METHOD__);

        return $url;
    }

    /**
     * Retrieve URL instance.
     *
     * @return \Magento\Framework\UrlInterface
     */
    public function getUrlInstance()
    {
        return $this->_url;
    }

    /**
     * Retrieve Request Path.
     *
     * @return string
     */
    public function getRequestPath()
    {
        return $this->_getData('request_path');
    }

    /**
     * Get category url.
     *
     * @return string
     */
    public function getUrl()
    {
        $urlPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($this, $this->getStoreId(), true,false);
        $suffix = $this->categoryUrlPathGenerator->getCategoryUrlSuffix($this->getStoreId());
        $exportUrlPath = explode("/", $urlPath);
        if (count($exportUrlPath) == 1) {
            $vendorUrl = trim($this->vendorHelper->getUrl($this->getVendor(),$exportUrlPath[0]),"/").$suffix;
        } else {
            $firstPath = $exportUrlPath[0];
            unset($exportUrlPath[0]);
            $secondPath = "";
            foreach ($exportUrlPath as $value) {
                $secondPath .= $value."/";
            }
            $secondPath = trim($secondPath,"/");
            $vendorUrl = trim($this->vendorHelper->getUrl($this->getVendor(),$firstPath),"/")."/".$secondPath.$suffix;
        }

        //new category url
        return trim($vendorUrl,'/');
    }

    /**
     * Format URL key from name or defined key.
     *
     * @param string $str
     *
     * @return string
     */
    public function formatUrlKey($str)
    {
        return $this->filter->translitUrl($str);
    }

    /**
     * Get all children categories IDs
     * included current category id.
     *
     * @param bool $asArray return result as array instead of comma-separated list of IDs
     *
     * @return array|string
     */
    public function getAllChildren($asArray = false)
    {
        $children = $this->getResource()->getAllChildren($this);
        if ($asArray) {
            return $children;
        } else {
            return implode(',', $children);
        }
    }

    /**
     * Check category id existing.
     *
     * @param int $id
     *
     * @return bool
     */
    public function checkId($id)
    {
        return $this->_getResource()->checkId($id);
    }

    /**
     * Retrieve Is Category has children flag.
     * under developed.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->_getResource()->getChildrenAmount($this) > 0;
    }

    /**
     * Retrieve categories by parent.
     *
     * @param int  $parent
     * @param int  $recursionLevel
     * @param bool $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     *
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getCategories($parent, $recursionLevel = 0, $sorted = false, $asCollection = false, $toLoad = true)
    {
        $categories = $this->getResource()->getCategories($parent, $recursionLevel, $sorted, $asCollection, $toLoad);

        return $categories;
    }

    /**
     * Return parent categories of current category.
     *
     * @return \Magento\Framework\DataObject[]|\Magento\Catalog\Model\Category[]
     */
    public function getParentCategories()
    {
        return $this->getResource()->getParentCategories($this);
    }

    /**
     * Return children categories of current category.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection|\Magento\Catalog\Model\Category[]
     */
    public function getChildrenCategories()
    {
        return $this->getResource()->getChildrenCategories($this);
    }

    /**
     * @return array|bool
     */
    public function validate()
    {

        //return $this->_getResource()->validate($this);
        $errors = [];
        if ($this->getName() && trim($this->getName()) == "") {
            $errors[] = __('Please enter name.');
        }

        if (!$this->helper->getEnabledVendorSubCat()) {
            if ($this->getLevel() > 1) {
                $errors[] = __('Cannot create sub category.');
            }
        }

        $errors1 = $this->getResource()->validate($this);
        if (is_array($errors1)) {
            $errors = array_merge($errors, $errors1);
        }

        $transport = new \Magento\Framework\DataObject(
            ['errors' => $errors]
        );
        $errors = $transport->getErrors();


        $errors1 = $this->getResource()->validate($this);
        if(is_array($errors1)) $errors = array_merge($errors, $errors1);


        $transport2 = new \Magento\Framework\DataObject(
            [
                'errors' => [],
                'url_key' => $this->getUrlKey(),
                'vendor_id' => $this->getVendorId(),
                'type'=> 'category'
            ]
        );

        $this->_eventManager->dispatch('page_url_key_validate', ['transport' => $transport2]);
        $errors2 = $transport2->getErrors();

        if (is_array($errors2)) {
            $errors = array_merge($errors, $errors2);
        }

        if (empty($errors)) {
            return true;
        }

        return $errors;
    }


    /**
     * Add reindexCallback.
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function afterSave()
    {
        $result = parent::afterSave();
        $this->_getResource()->addCommitCallback([$this, 'reindex']);
        $this->_cacheManager->clean([self::CACHE_TAG]);
        return $result;
    }

    /**
     * Before delete process.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return $this
     */
    public function beforeDelete()
    {
        if ($this->getResource()->isForbiddenToDelete($this->getId())) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Can\'t delete root category.'));
        }

        return parent::beforeDelete();
    }

    /**
     * Init indexing process after category delete.
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function afterDeleteCommit()
    {
        $this->reindex();

        return parent::afterDeleteCommit();
    }

    /**
     * Init indexing process after category save.
     */
    public function reindex()
    {
    }

    /**
     * @codeCoverageIgnoreStart
     *
     * @return string|null
     */
    public function getPath()
    {
        return $this->getData(self::KEY_PATH);
    }

    /**
     * get store id of vendor via customer.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getCustomer()->getStoreId();
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        return $this->getVendor()->getCustomer();
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendor) {
            return $this->vendor;
        } else {
            $this->vendor = \Magento\Framework\App\ObjectManager::getInstance()->create('Vnecoms\Vendors\Model\Vendor')
                ->load($this->getVendorId());

            return $this->vendor;
        }
    }

    /**
     * @return int|null
     */
    public function getPosition()
    {
        return $this->getData(self::KEY_POSITION);
    }

    /**
     * @return int
     */
    public function getChildrenCount()
    {
        return $this->getData('children_count');
    }

    /**
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::KEY_UPDATED_AT);
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsActive()
    {
        return $this->getData(self::KEY_IS_ACTIVE);
    }

    /**
     * @return int|null
     */
    public function getCategoryId()
    {
        return $this->getData('category_id');
    }

    /**
     * @return string|null
     */
    public function getUrlKey()
    {
        return $this->getData('url_key');
    }

    /**
     * @return \Magento\Catalog\Api\Data\CategoryTreeInterface[]|null
     */
    public function getChildrenData()
    {
        return $this->getData(self::KEY_CHILDREN_DATA);
    }

    /**
     * @param bool $isApproved
     * @param bool $statusFilter
     * @param bool $visibilityFilter
     * @return mixed
     */
    public function getProductCount($isApproved = false, $statusFilter = false, $visibilityFilter = false)
    {
        $count = $this->_getResource()->getProductCount($this, $isApproved, $statusFilter, $visibilityFilter);
        $this->_cacheManager->clean([self::CACHE_TAG]);
        $this->_cacheManager->clean(['ves_vendorscategory_category_product']);
        $this->setData(self::KEY_PRODUCT_COUNT, $count);

        return $this->getData(self::KEY_PRODUCT_COUNT);
    }

    /**
     * Retrieve children ids comma separated.
     *
     * @return string
     */
    public function getChildren()
    {
        return implode(',', $this->getResource()->getChildren($this, false));
    }

    /**
     * @return int
     */
    public function getVendorId()
    {
        return $this->getData(self::KEY_VENDOR_ID);
    }

    public function getRealId()
    {
        return $this->getData('real_category_id');
    }

    public function setRealId($id)
    {
        $this->setData('real_category_id', $id);

        return $this;
    }

    /**
     * Retrieve Name data wrapper.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_getData(self::KEY_NAME);
    }

    /**
     * Retrieve level.
     *
     * @return int
     */
    public function getLevel()
    {
        if (!$this->hasLevel()) {
            return count(explode('/', $this->getPath())) - 1;
        }

        return $this->getData(self::KEY_LEVEL);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getData(self::KEY_DESC);
    }

    /**
     * Set parent category ID.
     *
     * @param int $parentId
     *
     * @return $this
     */
    public function setParentId($parentId)
    {
        return $this->setData(self::KEY_PARENT_ID, $parentId);
    }

    /**
     * Set category name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::KEY_NAME, $name);
    }

    /**
     * @param int $vendorId
     *
     * @return $this
     */
    public function setVendorId($vendorId)
    {
        return $this->setData(self::KEY_VENDOR_ID, $vendorId);
    }

    /**
     * @param string $desc
     *
     * @return $this
     */
    public function setDescription($desc)
    {
        return $this->setData(self::KEY_DESC, $desc);
    }

    /**
     * Set whether category is active.
     *
     * @param bool $isActive
     *
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::KEY_IS_ACTIVE, $isActive);
    }

    /**
     * Set category position.
     *
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        return $this->setData(self::KEY_POSITION, $position);
    }

    /**
     * Set category level.
     *
     * @param int $level
     *
     * @return $this
     */
    public function setLevel($level)
    {
        return $this->setData(self::KEY_LEVEL, $level);
    }

    /**
     * @param string $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::KEY_UPDATED_AT, $updatedAt);
    }

    /**
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        return $this->setData(self::KEY_PATH, $path);
    }

    /**
     * Set product count.
     *
     * @param int $productCount
     *
     * @return $this
     */
    public function setProductCount($productCount)
    {
        return $this->setData(self::KEY_PRODUCT_COUNT, $productCount);
    }

    public function getIncludedInMenu()
    {
        return $this->getData('included_in_menu');
    }

    public function setIncludedInMenu($config)
    {
        $this->setData('included_in_menu', $config);

        return $this;
    }

    /**
     * @param \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterface[] $childrenData
     *
     * @return $this
     */
    public function setChildrenData(array $childrenData = null)
    {
        return $this->setData(self::KEY_CHILDREN_DATA, $childrenData);
    }
    //@codeCoverageIgnoreEnd

    /**
     * get root category id of vendor.
     *
     * @param int $vendorId
     */
    public function getRootId($vendorId)
    {
        if (!$vendorId) return;
        return $this->getResource()->getRootId($vendorId);
    }

    /**
     * Get category products collection.
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getProductCollection()
    {
        $collection = $this->_productCollectionFactory->create()->setStoreId(
            $this->getStoreId()
        );

        return $collection;
    }

    /**
     * Return Data Object data in array format.
     *
     * @return array
     *
     * @todo refactor with converter for AbstractExtensibleModel
     */
    public function __toArray()
    {
        $data = $this->_data;
        $hasToArray = function ($model) {
            return is_object($model) && method_exists($model, '__toArray') && is_callable([$model, '__toArray']);
        };
        foreach ($data as $key => $value) {
            if ($hasToArray($value)) {
                $data[$key] = $value->__toArray();
            } elseif (is_array($value)) {
                foreach ($value as $nestedKey => $nestedValue) {
                    if ($hasToArray($nestedValue)) {
                        $value[$nestedKey] = $nestedValue->__toArray();
                    }
                }
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Retrieve Stores where isset category Path
     * Return comma separated string.
     *
     * @param string $vendorId
     *
     * @return string
     */
    public function getPathWithOutRoot($vendorId)
    {
        $result = [];
        $path = array_reverse($this->getPathIds());
        foreach ($path as $itemId) {
            if ($itemId == $this->getRootId($vendorId)) {
                break;
            }
            $result[] = $itemId;
        }

        return implode(',', $result);
    }

    /**
     * @param $string
     * @return mixed
     */
    public function generalUrlKey(){
        if (!$this->getUrlKey()) {
            //generate url key and url path
            $this->setUrlKey($this->categoryUrlPathGenerator->getUrlKey($this))
                ->setUrlPath($this->categoryUrlPathGenerator->getUrlPath($this));
            if (!$this->isObjectNew()) {
                //if url path change
                if ($this->dataHasChangedFor('url_path')) {
                    $this->updateUrlPathForChildren();
                }
            }
        }
    }

    /**
     * @param Category $category
     */
    protected function updateUrlPathForChildren()
    {
        $children = $this->childrenCategoriesProvider->getChildren($this, true);
        foreach ($children as $child) {
            $this->updateUrlPathForCategory($child);
        }
    }

    /**
     * @param Category $category
     */
    protected function updateUrlPathForCategory(\Vnecoms\VendorsCategory\Model\Category $category)
    {
        $category->unsUrlPath();
        $category->setUrlPath($this->categoryUrlPathGenerator->getUrlPath($category));
    }

}
