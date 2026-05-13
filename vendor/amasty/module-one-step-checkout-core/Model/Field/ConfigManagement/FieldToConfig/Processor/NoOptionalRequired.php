<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\Processor;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\SaveConfigValue;
use Magento\Config\Model\Config\Source\Nooptreq;

class NoOptionalRequired implements ProcessorInterface
{
    /**
     * @var SaveConfigValue
     */
    private $saveConfigValue;

    public function __construct(SaveConfigValue $saveConfigValue)
    {
        $this->saveConfigValue = $saveConfigValue;
    }

    public function execute(Field $field, string $configPath): void
    {
        if (!$field->isEnabled()) {
            $this->saveConfigValue->execute($configPath, Nooptreq::VALUE_NO);
            return;
        }

        $this->saveConfigValue->execute(
            $configPath,
            $field->getIsRequired() ?
                Nooptreq::VALUE_REQUIRED :
                Nooptreq::VALUE_OPTIONAL
        );
    }
}
