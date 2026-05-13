<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\Processor;

use Amasty\CheckoutCore\Model\Field;

interface ProcessorInterface
{
    /**
     * @param Field $field
     * @param string $configPath
     * @return void
     */
    public function execute(Field $field, string $configPath): void;
}
