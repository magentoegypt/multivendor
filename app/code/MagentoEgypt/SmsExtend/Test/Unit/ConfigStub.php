<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Test\Unit;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Store configuration from an array (path => value); a missing path is null, as when nothing is stored.
 */
class ConfigStub implements ScopeConfigInterface
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(public array $values = [])
    {
    }

    public function getValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return $this->values[$path] ?? null;
    }

    public function isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return !empty($this->values[$path]);
    }
}
