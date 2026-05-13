<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\CouldNotSaveException;

class CategoryLinkRepository implements \Vnecoms\VendorsCategory\Api\CategoryLinkRepositoryInterface
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface          $productRepository
     */
    public function __construct(
        \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function save(\Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface $productLink)
    {
        $category = $this->categoryRepository->get($productLink->getCategoryId());
        $product = $this->productRepository->get($productLink->getSku());
        $productPositions = $category->getProductsPosition();
        $productPositions[$product->getId()] = $productLink->getPosition();
        $category->setPostedProducts($productPositions);
        try {
            $category->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Could not save product "%1" with position %2 to category %3',
                    $product->getId(),
                    $productLink->getPosition(),
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
    public function delete(\Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface $productLink)
    {
        return $this->deleteByIds($productLink->getCategoryId(), $productLink->getSku());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByIds($categoryId, $sku)
    {
        $category = $this->categoryRepository->get($categoryId);
        $product = $this->productRepository->get($sku);
        $productPositions = $category->getProductsPosition();

        $productID = $product->getId();
        if (!isset($productPositions[$productID])) {
            throw new InputException(__('Category does not contain specified product'));
        }
        $backupPosition = $productPositions[$productID];
        unset($productPositions[$productID]);

        $category->setPostedProducts($productPositions);
        try {
            $category->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Could not save product "%product" with position %position to category %category',
                    [
                        'product' => $product->getId(),
                        'position' => $backupPosition,
                        'category' => $category->getId(),
                    ]
                ),
                $e
            );
        }

        return true;
    }
}
