<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Category;

use Vnecoms\VendorsCategory\Model\Category;
use Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Vnecoms\VendorsCategory\Model\CategoryFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class DataProvider.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CategoryCollectionFactory $categoryCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request,
        CategoryFactory $categoryFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $categoryCollectionFactory->create();
        $this->collection->addFieldToSelect('*');
        $this->registry = $registry;
        $this->request = $request;
        $this->categoryFactory = $categoryFactory;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * Prepare meta data.
     *
     * @param array $meta
     *
     * @return array
     */
    public function prepareMeta($meta)
    {
        return $meta;
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $category = $this->getCurrentCategory();
        if ($category) {
            $categoryData = $category->getData();
          //  $categoryData = $this->filterFields($categoryData);
            if (isset($categoryData['image'])) {
                unset($categoryData['image']);
                $categoryData['image'][0]['name'] = $category->getData('image');
                $categoryData['image'][0]['url'] = $category->getImageUrl();
            }
            $this->loadedData[$category->getId()] = $categoryData;
        }

        return $this->loadedData;
    }

    /**
     * Get current category.
     *
     * @return Category
     *
     * @throws NoSuchEntityException
     */
    public function getCurrentCategory()
    {
        $category = $this->registry->registry('category');
        if ($category) {
            return $category;
        }
        $requestId = $this->request->getParam($this->requestFieldName);
        if ($requestId) {
            $category = $this->categoryFactory->create();
            $category->load($requestId);
            if (!$category->getId()) {
                throw NoSuchEntityException::singleField('id', $requestId);
            }
        }

        return $category;
    }

    /**
     * Filter fields.
     *
     * @param array $categoryData
     *
     * @return array
     */
    protected function filterFields($categoryData)
    {
        return array_diff_key($categoryData, array_flip([]));
    }
}
