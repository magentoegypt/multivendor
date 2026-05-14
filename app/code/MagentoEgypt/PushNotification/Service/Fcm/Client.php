<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Service\Fcm;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\SerializerInterface;
use MagentoEgypt\PushNotification\Helper\Config;

class Client
{
    const ENDPOINT_TEMPLATE = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AccessTokenProvider
     */
    private $tokenProvider;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        Config $config,
        AccessTokenProvider $tokenProvider,
        CurlFactory $curlFactory,
        SerializerInterface $serializer
    ) {
        $this->config = $config;
        $this->tokenProvider = $tokenProvider;
        $this->curlFactory = $curlFactory;
        $this->serializer = $serializer;
    }

    /**
     * Send a single message to one device token.
     *
     * @param string $deviceToken
     * @param array $message {title, body, image, data, action_url}
     * @return array {success: bool, status: int, body: string, retryable: bool, drop_token: bool}
     */
    public function send(string $deviceToken, array $message): array
    {
        $projectId = $this->config->getProjectId();
        if (!$projectId) {
            throw new LocalizedException(__('Firebase Project ID is not configured.'));
        }

        $payload = ['message' => $this->buildMessage($deviceToken, $message)];
        $body = $this->serializer->serialize($payload);

        $token = $this->tokenProvider->getToken();
        $response = $this->doPost($projectId, $token, $body);

        if ($response['status'] === 401) {
            $this->tokenProvider->invalidate();
            $token = $this->tokenProvider->getToken();
            $response = $this->doPost($projectId, $token, $body);
        }

        $response['drop_token'] = $this->isPermanentTokenError($response);
        $response['retryable']  = $response['status'] >= 500 || $response['status'] === 429;
        $response['success']    = $response['status'] >= 200 && $response['status'] < 300;
        return $response;
    }

    /**
     * @param string $projectId
     * @param string $token
     * @param string $body
     * @return array {status: int, body: string}
     */
    private function doPost(string $projectId, string $token, string $body): array
    {
        $curl = $this->curlFactory->create();
        $curl->setOption(CURLOPT_TIMEOUT, $this->config->getTimeout());
        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $curl->addHeader('Authorization', 'Bearer ' . $token);
        $curl->addHeader('Content-Type', 'application/json');
        $curl->post(sprintf(self::ENDPOINT_TEMPLATE, $projectId), $body);

        return [
            'status' => (int) $curl->getStatus(),
            'body'   => (string) $curl->getBody(),
        ];
    }

    /**
     * @param string $deviceToken
     * @param array $message
     * @return array
     */
    private function buildMessage(string $deviceToken, array $message): array
    {
        $payload = [
            'token' => $deviceToken,
            'notification' => array_filter([
                'title' => $message['title'] ?? null,
                'body'  => $message['body']  ?? null,
                'image' => $message['image'] ?? null,
            ], static fn($v) => $v !== null && $v !== ''),
        ];

        $data = $message['data'] ?? [];
        if (!empty($message['action_url'])) {
            $data['action_url'] = $message['action_url'];
            $data['click_action'] = $message['action_url'];
        }

        if (!empty($data)) {
            $payload['data'] = array_map('strval', $data);
        }

        return $payload;
    }

    /**
     * Permanent token errors per https://firebase.google.com/docs/cloud-messaging/manage-tokens
     */
    private function isPermanentTokenError(array $response): bool
    {
        if ($response['status'] === 404) {
            return true;
        }
        if ($response['status'] !== 400 && $response['status'] !== 403) {
            return false;
        }

        try {
            $decoded = $this->serializer->unserialize($response['body']);
        } catch (\Exception $e) {
            return false;
        }

        $status = $decoded['error']['status'] ?? '';
        $details = $decoded['error']['details'] ?? [];
        if ($status === 'UNREGISTERED' || $status === 'NOT_FOUND' || $status === 'INVALID_ARGUMENT') {
            foreach ($details as $detail) {
                if (($detail['errorCode'] ?? null) === 'UNREGISTERED'
                    || ($detail['errorCode'] ?? null) === 'INVALID_ARGUMENT') {
                    return true;
                }
            }
            return $status !== 'INVALID_ARGUMENT';
        }
        return false;
    }
}
