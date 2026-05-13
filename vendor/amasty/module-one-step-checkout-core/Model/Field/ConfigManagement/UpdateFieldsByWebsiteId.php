<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\DuplicateField;
use Amasty\CheckoutCore\Model\Field\GetDefaultField;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Amasty\CheckoutCore\Model\ResourceModel\Field\Collection\FilterByAttributeAndStore;
use Amasty\CheckoutCore\Model\ResourceModel\Field\CollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Website;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateFieldsByWebsiteId
{
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var DuplicateField
     */
    private $duplicateField;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var FilterByAttributeAndStore
     */
    private $filterByAttributeAndStore;

    /**
     * @var GetDefaultField
     */
    private $getDefaultField;

    /**
     * @var FieldResource
     */
    private $fieldResource;

    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        DuplicateField $duplicateField,
        CollectionFactory $collectionFactory,
        FilterByAttributeAndStore $filterByAttributeAndStore,
        GetDefaultField $getDefaultField,
        FieldResource $fieldResource
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->duplicateField = $duplicateField;
        $this->collectionFactory = $collectionFactory;
        $this->filterByAttributeAndStore = $filterByAttributeAndStore;
        $this->getDefaultField = $getDefaultField;
        $this->fieldResource = $fieldResource;
    }

    /**
     * @param int $attributeId
     * @param int $websiteId
     * @param bool $isEnabled
     * @param bool $isRequired
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     * @return void
     */
    public function execute(
        int $attributeId,
        int $websiteId,
        bool $isEnabled,
        bool $isRequired
    ): void {
        /** @var Website $website */
        $website = $this->websiteRepository->getById($websiteId);
        $storeIds = $website->getStoreIds();

        $collection = $this->collectionFactory->create();
        $this->filterByAttributeAndStore->execute($collection, $attributeId, $storeIds);

        $defaultField = $this->getDefaultField->execute($attributeId);
        if (!$defaultField) {
            return;
        }

        foreach ($storeIds as $storeId) {
            /** @var Field $field */
            $field = $collection->getItemByColumnValue(Field::STORE_ID, $storeId);

            if (!$field) {
                $field = $this->duplicateFieldForStoreId($defaultField, (int) $storeId);
            }

            $field->setIsEnabled($isEnabled);
            $field->setIsRequired($isRequired);
            $this->fieldResource->save($field);
        }
    }

    private function duplicateFieldForStoreId(Field $defaultField, int $storeId): Field
    {
        $field = $this->duplicateField->execute($defaultField);
        $field->setStoreId($storeId);
        return $field;
    }
}
