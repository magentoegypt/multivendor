<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OrderGranularity implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'per_vendor', 'label' => __('One Odoo sale.order per vendor (default)')],
            ['value' => 'master', 'label' => __('Single master Odoo sale.order')],
        ];
    }
}
