<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Metadata;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Customer\Model\AttributeMetadataConverter;
use Magento\Customer\Model\Attribute;
use Magento\Framework\Exception\NoSuchEntityException;

class Form
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
     * @param \Magento\Customer\Model\Metadata\Form $subject
     * @param array $attributes
     *
     * @return array
     */
    public function afterGetAttributes(\Magento\Customer\Model\Metadata\Form $subject, $attributes)
    {
        if (!isset($attributes['email'])) {
            foreach (CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY as $attributeCode) {
                try {
                    /** @var Attribute $customAttribute */
                    $customAttribute =
                        $this->eavAttributeRepository->get(AttributeProvider::ENTITY, $attributeCode);
                } catch (NoSuchEntityException $exception) {
                    continue;
                }

                if ($customAttribute->getData()) {
                    $attributes[$attributeCode] = $this->attributeMetadataConverter
                        ->createMetadataAttribute($customAttribute);
                }
            }
        }

        return $attributes;
    }
}
