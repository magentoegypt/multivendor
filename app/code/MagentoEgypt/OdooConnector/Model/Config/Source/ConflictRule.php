<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ConflictRule implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'magento_wins', 'label' => __('Magento wins')],
            ['value' => 'odoo_wins', 'label' => __('Odoo wins')],
            ['value' => 'last_write_wins', 'label' => __('Last write wins (by timestamp)')],
            ['value' => 'field_level', 'label' => __('Field-level authority')],
        ];
    }
}
