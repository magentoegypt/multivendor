<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToAttribute\Processor;

use Magento\Customer\Model\Attribute;

interface ProcessorInterface
{
    /**
     * @param Attribute $attribute
     * @param string $value
     * @param int $websiteId
     * @return void
     */
    public function execute(Attribute $attribute, string $value, int $websiteId): void;
}
