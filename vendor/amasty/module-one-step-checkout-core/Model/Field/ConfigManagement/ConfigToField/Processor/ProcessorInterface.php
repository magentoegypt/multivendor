<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\Processor;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ProcessorInterface
{
    /**
     * @param int $attributeId
     * @param string $value
     * @param int|null $websiteId
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function execute(int $attributeId, string $value, ?int $websiteId): void;
}
