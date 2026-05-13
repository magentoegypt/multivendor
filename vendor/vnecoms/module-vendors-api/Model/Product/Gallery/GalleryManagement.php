<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsApi\Model\Product\Gallery;

use Vnecoms\VendorsApi\Helper\Data as ApiHelper;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\Data\ProductInterface as Product;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Api\ImageContentValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GalleryManagement implements \Vnecoms\VendorsApi\Api\ProductAttributeMediaGalleryManagementInterface
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
     * @var ImageContentValidatorInterface
     */
    protected $contentValidator;

    /**
     * @param ApiHelper $helper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param ImageContentValidatorInterface $contentValidator
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ApiHelper $helper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        ImageContentValidatorInterface $contentValidator
    ) {
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->contentValidator = $contentValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function create($customerId, $sku, ProductAttributeMediaGalleryEntryInterface $entry)
    {
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        $product = $this->productRepository->get($sku);
        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }

        /** @var $entry ProductAttributeMediaGalleryEntryInterface */
        $entryContent = $entry->getContent();

        if (!$this->contentValidator->isValid($entryContent)) {
            throw new InputException(__('The image content is not valid.'));
        }

        $existingMediaGalleryEntries = $product->getMediaGalleryEntries();
        $existingEntryIds = [];
        if ($existingMediaGalleryEntries == null) {
            $existingMediaGalleryEntries = [$entry];
        } else {
            foreach ($existingMediaGalleryEntries as $existingEntries) {
                $existingEntryIds[$existingEntries->getId()] = $existingEntries->getId();
            }
            $existingMediaGalleryEntries[] = $entry;
        }
        $product->setMediaGalleryEntries($existingMediaGalleryEntries);
        try {
            $product = $this->productRepository->save($product);
        } catch (InputException $inputException) {
            throw $inputException;
        } catch (\Exception $e) {
            throw new StateException(__('Cannot save product.'));
        }

        foreach ($product->getMediaGalleryEntries() as $entry) {
            if (!isset($existingEntryIds[$entry->getId()])) {
                return $entry->getId();
            }
        }
        throw new StateException(__('Failed to save new media gallery entry.'));
    }

    /**
     * {@inheritdoc}
     */
    public function update($customerId, $sku, ProductAttributeMediaGalleryEntryInterface $entry)
    {
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        $product = $this->productRepository->get($sku);
        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }

        $existingMediaGalleryEntries = $product->getMediaGalleryEntries();

        if ($existingMediaGalleryEntries == null) {
            throw new NoSuchEntityException(__('There is no image with provided ID.'));
        }

        $found = false;
        foreach ($existingMediaGalleryEntries as $key => $existingEntry) {
            $entryTypes = (array)$entry->getTypes();
            $existingEntryTypes = (array)$existingMediaGalleryEntries[$key]->getTypes();
            $existingMediaGalleryEntries[$key]->setTypes(array_diff($existingEntryTypes, $entryTypes));
            if ($existingEntry->getId() == $entry->getId()) {
                $found = true;
                if ($entry->getFile()) {
                    $entry->setId(null);
                }
                $existingMediaGalleryEntries[$key] = $entry;
            }
        }

        if (!$found) {
            throw new NoSuchEntityException(__('There is no image with provided ID.'));
        }

        $product->setMediaGalleryEntries($existingMediaGalleryEntries);

        try {
            $this->productRepository->save($product);
        } catch (\Exception $exception) {
            throw new StateException(__('Cannot save product.'));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($customerId, $sku, $entryId)
    {
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        $product = $this->productRepository->get($sku);
        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }

        $existingMediaGalleryEntries = $product->getMediaGalleryEntries();
        if ($existingMediaGalleryEntries == null) {
            throw new NoSuchEntityException(__('There is no image with provided ID.'));
        }
        $found = false;
        foreach ($existingMediaGalleryEntries as $key => $entry) {
            if ($entry->getId() == $entryId) {
                unset($existingMediaGalleryEntries[$key]);
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new NoSuchEntityException(__('There is no image with provided ID.'));
        }
        $product->setMediaGalleryEntries($existingMediaGalleryEntries);
        $this->productRepository->save($product);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($customerId, $sku, $entryId)
    {
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        try {
            $product = $this->productRepository->get($sku);
        } catch (\Exception $exception) {
            throw new NoSuchEntityException(__('Such product doesn\'t exist'));
        }

        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }

        $mediaGalleryEntries = $product->getMediaGalleryEntries();
        foreach ($mediaGalleryEntries as $entry) {
            if ($entry->getId() == $entryId) {
                return $entry;
            }
        }

        throw new NoSuchEntityException(__('Such image doesn\'t exist'));
    }

    /**
     * {@inheritdoc}
     */
    public function getList($customerId, $sku)
    {
        $vendor = $this->helper->getVendorByCustomerId($customerId);
        $vendorId = $vendor->getId();
        $product = $this->productRepository->get($sku);
        if ($product->getVendorId() != $vendorId){
            throw new LocalizedException(__('You are not authorized.'));
        }
        return $product->getMediaGalleryEntries();
    }
}
