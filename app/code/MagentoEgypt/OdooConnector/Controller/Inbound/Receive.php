<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Controller\Inbound;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Logger\Logger;
use MagentoEgypt\OdooConnector\Model\Inbound\InboundProcessor;

/**
 * Inbound endpoint Odoo -> Magento (POST odooconnector/inbound/receive).
 * CSRF-exempt (external caller) but authenticated by an HMAC-SHA256 signature
 * over the raw body using the configured shared secret.
 */
class Receive extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    private JsonFactory $resultJsonFactory;
    private Config $config;
    private InboundProcessor $processor;
    private Json $json;
    private Logger $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $config,
        InboundProcessor $processor,
        Json $json,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->processor = $processor;
        $this->json = $json;
        $this->logger = $logger;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true; // CSRF token cannot be supplied by an external system; HMAC is the auth.
    }

    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        $body = (string)$this->getRequest()->getContent();
        $signature = (string)$this->getRequest()->getHeader('X-Odoo-Signature');

        $secret = (string)$this->config->getInboundSharedSecret();
        if ($secret === '') {
            return $result->setHttpResponseCode(503)->setData(['success' => false, 'message' => 'inbound shared secret not configured']);
        }

        $expected = hash_hmac('sha256', $body, $secret);
        if ($signature === '' || !hash_equals($expected, $signature)) {
            $this->logger->warning('Odoo inbound: invalid signature');

            return $result->setHttpResponseCode(401)->setData(['success' => false, 'message' => 'invalid signature']);
        }

        try {
            $envelope = (array)$this->json->unserialize($body);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(400)->setData(['success' => false, 'message' => 'invalid JSON body']);
        }

        $outcome = $this->processor->process($envelope);
        $ok = !in_array($outcome['result'] ?? '', ['error', 'failed'], true);

        return $result->setData(['success' => $ok, 'outcome' => $outcome]);
    }
}
