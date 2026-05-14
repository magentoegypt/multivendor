<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Service\Fcm;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use MagentoEgypt\PushNotification\Helper\Config;

class AccessTokenProvider
{
    const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    const SCOPE          = 'https://www.googleapis.com/auth/firebase.messaging';
    const CACHE_KEY      = 'magentoegypt_pushnotification_fcm_access_token';
    const SAFETY_MARGIN  = 60;

    /**
     * @var ServiceAccount
     */
    private $serviceAccount;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        ServiceAccount $serviceAccount,
        Curl $curl,
        SerializerInterface $serializer,
        CacheInterface $cache,
        Config $config
    ) {
        $this->serviceAccount = $serviceAccount;
        $this->curl = $curl;
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * @return string Bearer access token for FCM v1
     * @throws LocalizedException
     */
    public function getToken(): string
    {
        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached) {
            try {
                $data = $this->serializer->unserialize($cached);
                if (!empty($data['access_token']) && !empty($data['expires_at']) && $data['expires_at'] > time()) {
                    return (string) $data['access_token'];
                }
            } catch (\Exception $e) {
                // Fall through and re-mint.
            }
        }

        $account = $this->serviceAccount->load();
        $jwt = $this->buildJwt($account);

        $this->curl->setOption(CURLOPT_TIMEOUT, $this->config->getTimeout());
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->curl->post(self::TOKEN_ENDPOINT, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        $status = $this->curl->getStatus();
        $body = (string) $this->curl->getBody();

        if ($status < 200 || $status >= 300) {
            throw new LocalizedException(__('FCM OAuth2 token request failed with HTTP %1: %2', $status, $body));
        }

        try {
            $payload = $this->serializer->unserialize($body);
        } catch (\Exception $e) {
            throw new LocalizedException(__('FCM OAuth2 token response could not be parsed: %1', $e->getMessage()));
        }

        if (empty($payload['access_token'])) {
            throw new LocalizedException(__('FCM OAuth2 token response did not include an access_token.'));
        }

        $expiresIn = isset($payload['expires_in']) ? (int) $payload['expires_in'] : 3600;
        $expiresAt = time() + max(60, $expiresIn - self::SAFETY_MARGIN);

        $this->cache->save(
            $this->serializer->serialize([
                'access_token' => $payload['access_token'],
                'expires_at'   => $expiresAt,
            ]),
            self::CACHE_KEY,
            [],
            $expiresAt - time()
        );

        return (string) $payload['access_token'];
    }

    /**
     * Build the RS256-signed JWT assertion for the OAuth2 token exchange.
     *
     * @param array $account
     * @return string
     * @throws LocalizedException
     */
    private function buildJwt(array $account): string
    {
        try {
            $configuration = Configuration::forAsymmetricSigner(
                new Sha256(),
                InMemory::plainText($account['private_key']),
                InMemory::plainText($account['private_key'])
            );

            $now = new \DateTimeImmutable();
            $token = $configuration->builder()
                ->issuedBy((string) $account['client_email'])
                ->permittedFor(self::TOKEN_ENDPOINT)
                ->withClaim('scope', self::SCOPE)
                ->issuedAt($now)
                ->expiresAt($now->modify('+1 hour'))
                ->getToken($configuration->signer(), $configuration->signingKey());

            return $token->toString();
        } catch (\Throwable $e) {
            throw new LocalizedException(__('Failed to build the FCM OAuth2 JWT assertion: %1', $e->getMessage()));
        }
    }

    /**
     * Drop the cached access token (used on 401 responses from FCM).
     */
    public function invalidate(): void
    {
        $this->cache->remove(self::CACHE_KEY);
    }
}
