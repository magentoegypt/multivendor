<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\Processor;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\UpdateDefaultField;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\UpdateFieldsByWebsiteId;
use Magento\Config\Model\Config\Source\Nooptreq;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class NoOptionalRequired implements ProcessorInterface
{
    public const EXPECTED_VALUES = [
        Nooptreq::VALUE_NO,
        Nooptreq::VALUE_OPTIONAL,
        Nooptreq::VALUE_REQUIRED
    ];

    /**
     * @var UpdateDefaultField
     */
    private $updateDefaultField;

    /**
     * @var UpdateFieldsByWebsiteId
     */
    private $updateFieldsByWebsiteId;

    public function __construct(
        UpdateDefaultField $updateDefaultField,
        UpdateFieldsByWebsiteId $updateFieldsByWebsiteId
    ) {
        $this->updateDefaultField = $updateDefaultField;
        $this->updateFieldsByWebsiteId = $updateFieldsByWebsiteId;
    }

    /**
     * @param int $attributeId
     * @param string $value
     * @param int|null $websiteId
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function execute(int $attributeId, string $value, ?int $websiteId): void
    {
        if (!in_array($value, self::EXPECTED_VALUES)) {
            return;
        }

        $isEnabled = $value !== Nooptreq::VALUE_NO;
        $isRequired = $value === Nooptreq::VALUE_REQUIRED;

        if (!$websiteId) {
            $this->updateDefaultField->execute($attributeId, $isEnabled, $isRequired);
            return;
        }

        $this->updateFieldsByWebsiteId->execute($attributeId, $websiteId, $isEnabled, $isRequired);
    }
}
