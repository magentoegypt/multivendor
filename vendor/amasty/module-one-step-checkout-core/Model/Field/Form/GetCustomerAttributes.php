<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CustomerAttributes\Helper\Collection as CustomerAttributesHelper;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class GetCustomerAttributes
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        CollectionFactory $collectionFactory,
        AttributeFactory $attributeFactory,
        AttributeResource $attributeResource,
        ObjectManagerInterface $objectManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->attributeFactory = $attributeFactory;
        $this->attributeResource = $attributeResource;
        $this->objectManager = $objectManager;
    }

    /**
     * @param int $storeId
     * @return Attribute[]
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function execute(int $storeId): array
    {
        $customerAttributesHelper = $this->objectManager->create(CustomerAttributesHelper::class);

        $collection = $this->collectionFactory->create()
            ->addVisibleFilter();

        if ($storeId !== Field::DEFAULT_STORE_ID) {
            $collection->addFieldToFilter(
                'store_ids',
                [
                    ['eq' => $storeId],
                    ['like' => $storeId . ',%'],
                    ['like' => '%,' . $storeId],
                    ['like' => '%,' . $storeId . ',%']
                ]
            );
        }

        $collection = $customerAttributesHelper->addFilters(
            $collection,
            'eav_attribute',
            [
                "is_user_defined = 1",
                "attribute_code != 'customer_activated' "
            ]
        );

        $attributes = [];
        foreach ($collection->getAllIds() as $attributeId) {
            $attribute = $this->attributeFactory->create();
            $this->attributeResource->load($attribute, $attributeId);
            $attributes[] = $attribute;
        }

        return $attributes;
    }
}
