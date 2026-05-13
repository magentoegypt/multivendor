<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToAttribute;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToAttribute\Processor\ProcessorPool;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\GetAttributeCode;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\Attribute;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\NoSuchEntityException;

class UpdateAttributeFromConfig
{
    public const DEFAULT_WEBSITE_ID = 0;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var GetAttributeCode
     */
    private $getAttributeCode;

    /**
     * @var ProcessorPool
     */
    private $processorPool;

    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        GetAttributeCode $getAttributeCode,
        ProcessorPool $processorPool
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->getAttributeCode = $getAttributeCode;
        $this->processorPool = $processorPool;
    }

    /**
     * @param Value $configValue
     * @param string $value
     * @param int $websiteId
     * @return void
     * @throws \InvalidArgumentException
     */
    public function execute(Value $configValue, string $value, int $websiteId): void
    {
        $fieldConfig = $configValue->getData('field_config');
        if (!isset($fieldConfig['source_model'])) {
            return;
        }

        $sourceModel = $fieldConfig['source_model'];
        $processor = $this->processorPool->get($sourceModel);
        if ($processor) {
            $attribute = $this->getAttribute($configValue);
            if ($attribute) {
                $processor->execute($attribute, $value, $websiteId);
            }
        }
    }

    private function getAttribute(Value $configValue): ?Attribute
    {
        try {
            return $this->attributeRepository->get(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                $this->getAttributeCode->execute($configValue)
            );
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
