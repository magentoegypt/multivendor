<?php

namespace Vnecoms\VendorsCategory\Helper;

use Magento\Framework\App\Helper\Context as Context;
use Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface;
use Vnecoms\VendorsCategory\Model\Category as ModelCategory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Data.
 *
 * @author Vnecoms team <vnecoms.com>
 */
class Category extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USE_CATEGORY_CANONICAL_TAG = 'catalog/seo/category_canonical_tag';

    protected $_categoryPath;
    /**
     * Category factory.
     *
     * @var \Vnecoms\VendorsCategory\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    protected $_coreRegistry;

    public function __construct(
        Context $context,
        \Vnecoms\VendorsCategory\Model\CategoryFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $registry;
        $this->_categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Check if <link rel="canonical"> can be used for category.
     *
     * @param null|string|bool|int|Store $store
     *
     * @return bool
     */
    public function canUseCanonicalTag($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_USE_CATEGORY_CANONICAL_TAG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if a category can be shown.
     *
     * @param ModelCategory|int $category
     *
     * @return bool
     */
    public function canShow($category)
    {
        if (is_int($category)) {
            try {
                $category = $this->categoryRepository->get($category);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        } else {
            if (!$category->getId()) {
                return false;
            }
        }

        if (!$category->getIsActive()) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve category url.
     *
     * @param ModelCategory $category
     *
     * @return string
     */
    public function getCategoryUrl($category)
    {
        if ($category instanceof ModelCategory) {
            return $category->getUrl();
        }

        return $this->_categoryFactory->create()->setData($category->getData())->getUrl();
    }

    /**
     * Return current category path or get it from current category
     * and creating array of categories|product paths for breadcrumbs.
     *
     * @return string
     */
    public function getBreadcrumbPath()
    {
        if (!$this->_categoryPath) {
            $path = [];
            $category = $this->getCategory();
            if ($category) {
                $pathInStore = $category->getPathWithOutRoot($this->getVendor()->getId());
                $pathIds = array_reverse(explode(',', $pathInStore));

                $categories = $category->getParentCategories();

                // add category path breadcrumb
                foreach ($pathIds as $categoryId) {
                    if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path['category'.$categoryId] = [
                            'label' => $categories[$categoryId]->getName(),
                            'link' => $this->_isCategoryLink($categoryId) ? $categories[$categoryId]->getUrl() : '',
                        ];
                    }
                }
            }

            if ($this->getProduct()) {
                $path['product'] = ['label' => $this->getProduct()->getName()];
            }

            $this->_categoryPath = $path;
        }

        return $this->_categoryPath;
    }

    /**
     * Return current category object.
     *
     * @return \Vnecoms\VendorsCategory\Model\Category|null
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('current_vendor_category');
    }

    /**
     * Retrieve current Product object.
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('current_product');
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    /**
     * Check is category link.
     *
     * @param int $categoryId
     *
     * @return bool
     */
    protected function _isCategoryLink($categoryId)
    {
        if ($this->getProduct()) {
            return true;
        }
        if ($categoryId != $this->getCategory()->getId()) {
            return true;
        }

        return false;
    }
}
