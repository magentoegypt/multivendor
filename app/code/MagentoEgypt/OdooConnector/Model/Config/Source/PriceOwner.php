<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PriceOwner implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'magento', 'label' => __('Magento (catalog price authoritative)')],
            ['value' => 'odoo', 'label' => __('Odoo (ERP price authoritative)')],
        ];
    }
}
