<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Amasty\CheckoutCore\Model\ResourceModel\Field\Collection;
use Amasty\CheckoutCore\Model\ResourceModel\Field\CollectionFactory;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Field extends AbstractModel
{
    public const XML_PATH_CONFIG = 'customer/address/';
    public const MAGENTO_REQUIRE_CONFIG_VALUE = 'req';
    public const DEFAULT_STORE_ID = 0;

    public const ID = 'id';
    public const ATTRIBUTE_ID = 'attribute_id';
    public const STORE_ID = 'store_id';
    public const REQUIRED = 'required';
    public const ENABLED = 'enabled';
    public const SORT_ORDER = 'sort_order';
    public const LABEL = 'label';

    /**
     * @var ResourceModel\Field\CollectionFactory
     */
    protected $attributeCollectionFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        FieldResource $resource,
        Collection $resourceCollection,
        CollectionFactory $attributeCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init(FieldResource::class);
    }

    public function getInheritedAttributes()
    {
        return [
            'region_id' => 'region',
            'vat_is_valid' => 'vat_id',
            'vat_request_id' => 'vat_id',
            'vat_request_date' => 'vat_id',
            'vat_request_success' => 'vat_id',
        ];
    }

    /**
     * @param int $storeId
     *
     * @return \Amasty\CheckoutCore\Model\Field[]
     */
    public function getConfig($storeId)
    {
        /** @var Collection $attributeCollection */
        $attributeCollection = $this->getAttributeCollectionByStoreId($storeId);

        $result = [];

        /** @var \Amasty\CheckoutCore\Model\Field $item */
        foreach ($attributeCollection->getItems() as $item) {
            $item->setFieldDepend('checkout');
            $result[$item->getData('attribute_code')] = $item;

            if ($storeId != self::DEFAULT_STORE_ID && $item->getStoreId() == self::DEFAULT_STORE_ID) {
                $result[$item->getData('attribute_code')]['use_default'] = 1;
            }
        }

        return $result;
    }

    /**
     * @param int $storeId
     *
     * @return ResourceModel\Field\Collection
     */
    public function getAttributeCollectionByStoreId($storeId = self::DEFAULT_STORE_ID)
    {
        return  $this->attributeCollectionFactory->create()->getAttributeCollectionByStoreId($storeId);
    }

    public function setAttributeId(?int $attributeId): void
    {
        $this->setData(self::ATTRIBUTE_ID, $attributeId);
    }

    public function getAttributeId(): ?int
    {
        return $this->getData(self::ATTRIBUTE_ID) !== null ?
            (int) $this->getData(self::ATTRIBUTE_ID) :
            null;
    }

    public function setStoreId(?int $storeId): void
    {
        $this->setData(self::STORE_ID, $storeId);
    }

    public function getStoreId(): ?int
    {
        return $this->getData(self::STORE_ID) !== null ?
            (int) $this->getData(self::STORE_ID) :
            null;
    }

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->setData(self::ENABLED, (int) $isEnabled);
    }

    public function isEnabled(): bool
    {
        return (bool) $this->getData(self::ENABLED);
    }

    public function setIsRequired(bool $isRequired): void
    {
        $this->setData(self::REQUIRED, (int) $isRequired);
    }

    public function getIsRequired(): bool
    {
        return (bool) $this->getData(self::REQUIRED);
    }

    public function setSortOrder(?int $sortOrder): void
    {
        $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getSortOrder(): ?int
    {
        return $this->getData(self::SORT_ORDER) !== null ?
            (int) $this->getData(self::SORT_ORDER) :
            null;
    }

    public function setLabel(?string $label): void
    {
        $this->setData(self::LABEL, $label);
    }

    public function getLabel(): ?string
    {
        return $this->getData(self::LABEL);
    }
}
