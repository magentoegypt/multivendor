<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField;

use Amasty\CheckoutCore\Cache\InvalidateCheckoutCache;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\Processor\ProcessorPool;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ProcessConfigValue
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var GetAttributeCode
     */
    private $getAttributeCode;

    /**
     * @var InvalidateCheckoutCache
     */
    private $invalidateCheckoutCache;

    /**
     * @var ProcessorPool
     */
    private $processorPool;

    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        GetAttributeCode $getAttributeCode,
        InvalidateCheckoutCache $invalidateCheckoutCache,
        ProcessorPool $processorPool
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->getAttributeCode = $getAttributeCode;
        $this->invalidateCheckoutCache = $invalidateCheckoutCache;
        $this->processorPool = $processorPool;
    }

    /**
     * @param Value $configValue
     * @param string $value
     * @param int|null $websiteId
     * @return void
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws \InvalidArgumentException
     */
    public function execute(Value $configValue, string $value, ?int $websiteId): void
    {
        $fieldConfig = $configValue->getData('field_config');
        if (!isset($fieldConfig['source_model'])) {
            return;
        }

        $sourceModel = $fieldConfig['source_model'];
        $processor = $this->processorPool->get($sourceModel);
        if ($processor) {
            $attributeId = $this->getAttributeId($this->getAttributeCode->execute($configValue));
            if ($attributeId) {
                $processor->execute($attributeId, $value, $websiteId);
            }
            $this->invalidateCheckoutCache->execute();
        }
    }

    /**
     * @param string $attributeCode
     * @return int
     */
    private function getAttributeId(string $attributeCode): ?int
    {
        try {
            $attribute = $this->attributeRepository->get(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                $attributeCode
            );

            return (int) $attribute->getAttributeId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
