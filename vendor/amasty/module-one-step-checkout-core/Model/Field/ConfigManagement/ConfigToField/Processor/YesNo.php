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
use Amasty\CheckoutCore\Model\Field\ConfigManagement\YesNoOptions;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class YesNo implements ProcessorInterface
{
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
        if ($value !== YesNoOptions::VALUE_NO && $value !== YesNoOptions::VALUE_YES) {
            return;
        }

        $isEnabled = $value === YesNoOptions::VALUE_YES;

        if (!$websiteId) {
            $this->updateDefaultField->execute($attributeId, $isEnabled, false);
            return;
        }

        $this->updateFieldsByWebsiteId->execute($attributeId, $websiteId, $isEnabled, false);
    }
}
