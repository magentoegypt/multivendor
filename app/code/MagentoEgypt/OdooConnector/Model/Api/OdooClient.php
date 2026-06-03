<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Api;

use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Logger\Logger;

/**
 * Thin Odoo 19 external-API client over JSON-RPC (/jsonrpc).
 *
 * Magento -> Odoo writes call executeKw(); test/diagnostics call version().
 * Auth uses an Odoo API key (see Helper\Config::getApiKey) as the password
 * argument, per Odoo's external API. uid is cached per store for the request.
 */
class OdooClient
{
    private const ENDPOINT_PATH = '/jsonrpc';
    private const CONNECT_TIMEOUT = 10;
    private const TIMEOUT = 30;

    private CurlFactory $curlFactory;
    private Json $json;
    private Config $config;
    private Logger $logger;

    /** @var array<string, int> */
    private array $uidCache = [];

    private int $requestId = 0;

    public function __construct(
        CurlFactory $curlFactory,
        Json $json,
        Config $config,
        Logger $logger
    ) {
        $this->curlFactory = $curlFactory;
        $this->json = $json;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * common.version — no authentication required. Useful for connection tests.
     *
     * @param int|string|null $store
     * @return array<string, mixed>
     * @throws OdooException
     */
    public function version($store = null): array
    {
        return (array)$this->call('common', 'version', [], $store);
    }

    /**
     * Authenticate and return the Odoo uid (cached per store for this request).
     *
     * @param int|string|null $store
     * @throws OdooException
     */
    public function authenticate($store = null): int
    {
        $cacheKey = (string)($store ?? 'default');
        if (isset($this->uidCache[$cacheKey])) {
            return $this->uidCache[$cacheKey];
        }

        $db = (string)$this->config->getOdooDb($store);
        $user = (string)$this->config->getApiUser($store);
        $apiKey = (string)$this->config->getApiKey($store);

        if ($db === '' || $user === '' || $apiKey === '') {
            throw new OdooException(__('Odoo credentials are incomplete — set URL, database, API user and API key.'));
        }

        $uid = $this->call('common', 'authenticate', [$db, $user, $apiKey, (object)[]], $store);
        if (!$uid) {
            throw new OdooException(__('Odoo authentication failed — check the database name, API user and API key.'));
        }

        return $this->uidCache[$cacheKey] = (int)$uid;
    }

    /**
     * object.execute_kw — the workhorse for create/write/read against any model.
     *
     * @param array<int, mixed> $args
     * @param array<string, mixed> $kwargs
     * @param int|string|null $store
     * @return mixed
     * @throws OdooException
     */
    public function executeKw(string $model, string $method, array $args = [], array $kwargs = [], $store = null)
    {
        $db = (string)$this->config->getOdooDb($store);
        $apiKey = (string)$this->config->getApiKey($store);
        $uid = $this->authenticate($store);

        // Tag every connector-originated call with magento_sync so the Odoo
        // addon's automated actions skip re-enqueuing our own writes (loop guard).
        $kwargs['context'] = array_merge($kwargs['context'] ?? [], ['magento_sync' => true]);

        return $this->call(
            'object',
            'execute_kw',
            [$db, $uid, $apiKey, $model, $method, $args, (object)$kwargs],
            $store
        );
    }

    /**
     * Issue a single JSON-RPC call and return its `result`.
     *
     * @param array<int, mixed> $args
     * @param int|string|null $store
     * @return mixed
     * @throws OdooException
     */
    private function call(string $service, string $method, array $args, $store = null)
    {
        $base = $this->config->getOdooUrl($store);
        if (!$base) {
            throw new OdooException(__('Odoo URL is not configured.'));
        }
        $url = $base . self::ENDPOINT_PATH;

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => [
                'service' => $service,
                'method' => $method,
                'args' => $args,
            ],
            'id' => ++$this->requestId,
        ];

        $curl = $this->curlFactory->create();
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('Accept', 'application/json');
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        $curl->setOption(CURLOPT_TIMEOUT, self::TIMEOUT);

        $this->debug(sprintf('-> %s.%s @ %s', $service, $method, $url));

        try {
            $curl->post($url, $this->json->serialize($payload));
        } catch (\Throwable $e) {
            throw new OdooException(__('Could not reach Odoo at %1: %2', $url, $e->getMessage()), $e);
        }

        $status = (int)$curl->getStatus();
        $body = (string)$curl->getBody();
        $this->debug(sprintf('<- %s.%s HTTP %d', $service, $method, $status));

        if ($status === 0 || $body === '') {
            throw new OdooException(__('Empty response from Odoo (HTTP %1) for %2.%3.', $status, $service, $method));
        }

        try {
            $decoded = $this->json->unserialize($body);
        } catch (\Throwable $e) {
            throw new OdooException(__('Malformed Odoo response for %1.%2.', $service, $method));
        }

        if (is_array($decoded) && isset($decoded['error'])) {
            $message = $decoded['error']['data']['message']
                ?? $decoded['error']['message']
                ?? 'Unknown Odoo error';
            throw new OdooException(__('Odoo error on %1.%2: %3', $service, $method, $message));
        }

        if ($status >= 400) {
            throw new OdooException(__('Odoo HTTP error %1 on %2.%3.', $status, $service, $method));
        }

        return is_array($decoded) && array_key_exists('result', $decoded) ? $decoded['result'] : null;
    }

    private function debug(string $message): void
    {
        if (in_array($this->config->getLogLevel(), ['INFO', 'DEBUG'], true)) {
            $this->logger->debug($message);
        }
    }
}
