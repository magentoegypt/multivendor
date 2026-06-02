<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogLevel implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'OFF', 'label' => __('Off')],
            ['value' => 'ERROR', 'label' => __('Errors only')],
            ['value' => 'INFO', 'label' => __('Info')],
            ['value' => 'DEBUG', 'label' => __('Debug (verbose)')],
        ];
    }
}
