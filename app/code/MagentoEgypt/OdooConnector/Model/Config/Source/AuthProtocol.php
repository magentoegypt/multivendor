<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AuthProtocol implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'jsonrpc', 'label' => __('JSON-RPC (recommended)')],
            ['value' => 'xmlrpc', 'label' => __('XML-RPC')],
        ];
    }
}
