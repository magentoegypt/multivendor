<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToAttribute\Processor;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateAttribute;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\YesNoOptions;
use Magento\Customer\Model\Attribute;

class YesNo implements ProcessorInterface
{
    /**
     * @var UpdateAttribute
     */
    private $updateAttribute;

    public function __construct(UpdateAttribute $updateAttribute)
    {
        $this->updateAttribute = $updateAttribute;
    }

    public function execute(Attribute $attribute, string $value, int $websiteId): void
    {
        if ($value !== YesNoOptions::VALUE_NO && $value !== YesNoOptions::VALUE_YES) {
            return;
        }

        $this->updateAttribute->execute(
            $attribute,
            $value === YesNoOptions::VALUE_YES,
            false,
            $websiteId
        );
    }
}
