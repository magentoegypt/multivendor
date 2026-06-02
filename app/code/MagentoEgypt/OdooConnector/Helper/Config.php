<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Store-scoped reader for odooconnector/* configuration.
 *
 * Mirrors the house pattern (see MagentoEgypt\Khazenly\Helper\Data) of an
 * XML_PATH prefix + ScopeInterface::SCOPE_STORE, and decrypts the obscure
 * credential fields via the framework encryptor.
 */
class Config extends AbstractHelper
{
    public const XML_PATH = 'odooconnector/';

    private EncryptorInterface $encryptor;

    public function __construct(Context $context, EncryptorInterface $encryptor)
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * @param int|string|null $store
     */
    public function getValue(string $path, $store = null): ?string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH . $path, ScopeInterface::SCOPE_STORE, $store);

        return $value === null ? null : (string)$value;
    }

    /**
     * @param int|string|null $store
     */
    public function isFlag(string $path, $store = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH . $path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Decrypt an obscure (encrypted) field, returning null when empty.
     *
     * @param int|string|null $store
     */
    public function getDecrypted(string $path, $store = null): ?string
    {
        $value = $this->getValue($path, $store);
        if ($value === null || $value === '') {
            return null;
        }

        return $this->encryptor->decrypt($value);
    }

    /**
     * @param int|string|null $store
     */
    public function getOdooUrl($store = null): ?string
    {
        $url = $this->getValue('connection/odoo_url', $store);

        return $url ? rtrim($url, '/') : null;
    }

    /**
     * @param int|string|null $store
     */
    public function getOdooDb($store = null): ?string
    {
        return $this->getValue('connection/odoo_db', $store);
    }

    /**
     * @param int|string|null $store
     */
    public function getApiUser($store = null): ?string
    {
        return $this->getValue('connection/api_user', $store);
    }

    /**
     * @param int|string|null $store
     */
    public function getApiKey($store = null): ?string
    {
        return $this->getDecrypted('connection/api_key', $store);
    }

    /**
     * @param int|string|null $store
     */
    public function getInboundSharedSecret($store = null): ?string
    {
        return $this->getDecrypted('connection/inbound_shared_secret', $store);
    }

    /**
     * @param int|string|null $store
     */
    public function getAuthProtocol($store = null): string
    {
        return $this->getValue('connection/auth_protocol', $store) ?: 'jsonrpc';
    }

    /**
     * Per-website Odoo company id (multi-company), or null to use Odoo's default.
     *
     * @param int|string|null $store
     */
    public function getOdooCompanyId($store = null): ?int
    {
        $value = $this->getValue('connection/odoo_company_id', $store);

        return ($value !== null && $value !== '' && (int)$value > 0) ? (int)$value : null;
    }

    /**
     * @param int|string|null $store
     */
    public function isDomainEnabled(string $domain, $store = null): bool
    {
        return $this->isFlag('domains/enable_' . $domain, $store);
    }

    /**
     * @param int|string|null $store
     */
    public function getConflictRule(string $domain, $store = null): ?string
    {
        return $this->getValue('domains/conflict_rule_' . $domain, $store);
    }

    /**
     * @param int|string|null $store
     */
    public function getOrderGranularity($store = null): string
    {
        return $this->getValue('domains/order_granularity', $store) ?: 'per_vendor';
    }

    /**
     * @param int|string|null $store
     */
    public function getProductPriceOwner($store = null): string
    {
        return $this->getValue('domains/product_price_owner', $store) ?: 'magento';
    }

    /**
     * @param int|string|null $store
     */
    public function getMaxAttempts($store = null): int
    {
        $value = (int)$this->getValue('processing/max_attempts', $store);

        return $value > 0 ? $value : 5;
    }

    /**
     * @param int|string|null $store
     */
    public function getBatchSize($store = null): int
    {
        $value = (int)$this->getValue('processing/batch_size', $store);

        return $value > 0 ? $value : 50;
    }

    /**
     * @param int|string|null $store
     */
    public function getBackoffBaseMinutes($store = null): int
    {
        $value = (int)$this->getValue('processing/backoff_base_minutes', $store);

        return $value > 0 ? $value : 1;
    }

    /**
     * @param int|string|null $store
     */
    public function getLogLevel($store = null): string
    {
        return $this->getValue('logging/log_level', $store) ?: 'ERROR';
    }
}
