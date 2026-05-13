<?php

namespace Vnecoms\VendorsApi\Model;

use Vnecoms\VendorsApi\Helper\Data as ApiHelper;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ObjectManager;

class ProductCustomOptionRepository implements \Vnecoms\VendorsApi\Api\ProductCustomOptionRepositoryInterface
{
    /**
     * @var ApiHelper
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Option
     */
    protected $optionResource;

    /**
     * ProductCustomOptionRepository constructor.
     * @param ApiHelper $helper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param MetadataPool|null $metadataPool
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory|null $collectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option $optionResource
     */
    public function __construct
    (
        ApiHelper $helper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool = null,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $collectionFactory = null,
        \Magento\Catalog\Model\ResourceModel\Product\Option $optionResource
    )
    {
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->metadataPool = $metadataPool ?: ObjectManager::getInstance()
            ->get(\Magento\Framework\EntityManager\MetadataPool::class);
        $this->collectionFactory = $collectionFactory ?: ObjectManager::getInstance()
            ->get(\Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory::class);
        $this->optionResource = $optionResource;
    }

    /**
     * Get Product Options
     * @param ProductInterface $product
     * @param bool $requiredOnly
     * @return mixed
     */
    public function getProductOptions(ProductInterface $product, $requiredOnly = false)
    {
        return $this->collectionFactory->create()->getProductOptions(
            $product->getEntityId(),
            $product->getStoreId(),
            $requiredOnly
        );
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $entity
     * @return bool
     * @throws \Exception
     */
    public function delete(\Magento\Catalog\Api\Data\ProductCustomOptionInterface $entity)
    {
        $this->optionResource->delete($entity);
        return true;
    }

    /**
     * Get the list of custom options for a specific product
     * @param int $customerId
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductCustomOptionInterface[]
     */
    public function getList($customerId, $sku){
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        $product = $this->productRepository->get($sku, true);
        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }
        return $product->getOptions() ?: [];
    }

    /**
     * Get custom option for a specific product
     * @param int $customerId
     * @param string $sku
     * @param int $optionId
     * @return \Magento\Catalog\Api\Data\ProductCustomOptionInterface
     */
    public function get($customerId, $sku, $optionId){
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        $product = $this->productRepository->get($sku);
        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }
        $option = $product->getOptionById($optionId);
        if ($option === null) {
            throw NoSuchEntityException::singleField('optionId', $optionId);
        }
        return $option;
    }

    /**
     * Save Custom Option
     * @param int $customerId
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
     * @return \Magento\Catalog\Api\Data\ProductCustomOptionInterface
     */
    public function save($customerId, \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option){
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        $productSku = $option->getProductSku();
        if (!$productSku) {
            throw new CouldNotSaveException(__('ProductSku should be specified'));
        }
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->get($productSku);
        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $option->setData('product_id', $product->getData($metadata->getLinkField()));
        $option->setData('store_id', $product->getStoreId());
        if ($option->getOptionId()) {
            $options = $product->getOptions();
            if (!$options) {
                $options = $this->getProductOptions($product);
            }
            $persistedOption = array_filter($options, function ($iOption) use ($option) {
                return $option->getOptionId() == $iOption->getOptionId();
            });
            $persistedOption = reset($persistedOption);

            if (!$persistedOption) {
                throw new NoSuchEntityException();
            }
            $originalValues = $persistedOption->getValues();
            $newValues = $option->getData('values');
            if ($newValues) {
                if (isset($originalValues)) {
                    $newValues = $this->markRemovedValues($newValues, $originalValues);
                }
                $option->setData('values', $newValues);
            }
        }
        $option->save();
        return $option;
    }

    /**
     * @param int $customerId
     * @param string $sku
     * @param int $optionId
     * @return bool
     */
    public function deleteByIdentifier($customerId, $sku, $optionId){
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        $product = $this->productRepository->get($sku, true);
        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }
        $options = $product->getOptions();
        $option = $product->getOptionById($optionId);
        if ($option === null) {
            throw NoSuchEntityException::singleField('optionId', $optionId);
        }
        unset($options[$optionId]);
        try {
            $this->delete($option);
            if (empty($options)) {
                $this->productRepository->save($product);
            }
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not remove custom option'));
        }
        return true;
    }
}