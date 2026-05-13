<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes;

use Amasty\CheckoutCore\Model\Customer\Address\Attribute\CanChangeIfAttributeIsRequired;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateAttribute
{
    public const DEFAULT_WEBSITE_ID = 0;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    /**
     * @var CanChangeIfAttributeIsRequired
     */
    private $canChangeIfAttributeIsRequired;

    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        AttributeResource $attributeResource,
        CanChangeIfAttributeIsRequired $canChangeIfAttributeIsRequired
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->attributeResource = $attributeResource;
        $this->canChangeIfAttributeIsRequired = $canChangeIfAttributeIsRequired;
    }

    /**
     * @param Attribute $attribute
     * @param bool $isEnabled
     * @param bool $isRequired
     * @param int $websiteId
     * @return void
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function execute(Attribute $attribute, bool $isEnabled, bool $isRequired, int $websiteId): void
    {
        if ($websiteId === self::DEFAULT_WEBSITE_ID) {
            $attribute->setData('is_visible', $isEnabled);

            if ($this->canChangeRequiredValue($attribute)) {
                $attribute->setIsRequired($isRequired);
            }
        } else {
            $website = $this->websiteRepository->getById($websiteId);

            $attribute->setWebsite($website);
            $attribute->setData('scope_is_visible', $isEnabled);

            if ($this->canChangeRequiredValue($attribute)) {
                $attribute->setData('scope_is_required', $isRequired);
            }
        }

        $this->attributeResource->save($attribute);
    }

    private function canChangeRequiredValue(Attribute $attribute): bool
    {
        return $this->canChangeIfAttributeIsRequired->execute($attribute->getAttributeCode());
    }
}
