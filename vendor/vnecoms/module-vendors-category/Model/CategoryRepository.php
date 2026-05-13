<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryRepository implements \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface
{
    protected $vendorSession;

    /**
     * @var \Vnecoms\VendorsCategory\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category
     */
    protected $categoryResource;

    /**
     * @param \Vnecoms\VendorsCategory\Model\CategoryFactory        $categoryFactory
     * @param \Vnecoms\VendorsCategory\Model\ResourceModel\Category $categoryResource
     */
    public function __construct(
        \Vnecoms\VendorsCategory\Model\CategoryFactory $categoryFactory,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category $categoryResource
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
    }

    /**
     * {@inheritdoc}
     */
    public function save(\Vnecoms\VendorsCategory\Api\Data\CategoryInterface $category)
    {
        if ($category->getId()) {
            $category = $this->get($category->getId());
            $existingData = $category->getData();

            if (isset($existingData['image']) && is_array($existingData['image'])) {
                if (!empty($existingData['image']['delete'])) {
                    $existingData['image'] = null;
                } else {
                    if (isset($existingData['image'][0]['name']) && isset($existingData['image'][0]['tmp_name'])) {
                        $existingData['image'] = $existingData['image'][0]['name'];
                    } else {
                        unset($existingData['image']);
                    }
                }
            }
        } else {
            $parentId = $category->getParentId();
            $parentCategory = $this->get($parentId);
            $existingData['path'] = $parentCategory->getPath();
            $existingData['parent_id'] = $parentId;
        }
        $category->addData($existingData);
        try {
            $this->validateCategory($category);
            $this->categoryResource->save($category);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Could not save category: %1',
                    $e->getMessage()
                ),
                $e
            );
        }

        return $this->get($category->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function get($categoryId)
    {
        /** @var Category $category */
        $category = $this->categoryFactory->create();
        $category->load($categoryId);
        if (!$category->getId()) {
            throw NoSuchEntityException::singleField('id', $categoryId);
        }

        return $category;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(\Vnecoms\VendorsCategory\Api\Data\CategoryInterface $category)
    {
        try {
            $categoryId = $category->getId();
            $this->categoryResource->delete($category);
        } catch (\Exception $e) {
            throw new StateException(
                __(
                    'Cannot delete category with id %1',
                    $category->getId()
                ),
                $e
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByIdentifier($categoryId)
    {
        $category = $this->get($categoryId);

        return  $this->delete($category);
    }

    /**
     * @param Category $category
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateCategory(Category $category)
    {
        $validate = $category->validate();
        if ($validate !== true) {
            foreach ($validate as $code => $error) {
                if ($error === true) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Attribute "%1" is required.', $error)
                    );
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__($error));
                }
            }
        }
    }

    /**
     * @return \Vnecoms\Vendors\Model\Session
     */
    protected function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        } else {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\Vendors\Model\Session');

            return $this->vendorSession->getVendor();
        }
    }
}
