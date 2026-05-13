<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Quote\Model\Quote\Address;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeMetadataConverter;
use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address\CustomAttributeListInterface;

class CustomAttributeListPlugin
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $eavAttributeRepository;

    /**
     * @var AttributeMetadataConverter
     */
    private $attributeMetadataConverter;

    public function __construct(
        AttributeRepositoryInterface $eavAttributeRepository,
        AttributeMetadataConverter $attributeMetadataConverter
    ) {
        $this->eavAttributeRepository = $eavAttributeRepository;
        $this->attributeMetadataConverter = $attributeMetadataConverter;
    }

    /**
     * @param CustomAttributeListInterface $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetAttributes(CustomAttributeListInterface $subject, array $result): array
    {
        foreach (CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY as $attributeCode) {
            try {
                /** @var Attribute $customAttribute */
                $customAttribute =
                    $this->eavAttributeRepository->get(AttributeProvider::ENTITY, $attributeCode);
            } catch (NoSuchEntityException $exception) {
                continue;
            }

            if ($customAttribute->getAttributeCode()) {
                $result[$customAttribute->getAttributeCode()] = $this->attributeMetadataConverter
                    ->createMetadataAttribute($customAttribute);
            }
        }

        return $result;
    }
}
