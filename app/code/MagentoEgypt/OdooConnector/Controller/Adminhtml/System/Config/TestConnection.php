<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;

/**
 * AJAX endpoint behind the "Test Connection" button — checks Odoo reachability + auth.
 */
class TestConnection extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_OdooConnector::config';

    private JsonFactory $resultJsonFactory;
    private OdooClient $client;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OdooClient $client
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->client = $client;
    }

    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();

        try {
            $version = $this->client->version();
            $uid = $this->client->authenticate();

            return $result->setData([
                'success' => true,
                'message' => (string)__(
                    'Connected to Odoo %1 (uid %2).',
                    $version['server_version'] ?? 'unknown',
                    $uid
                ),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string)__('Connection failed: %1', $e->getMessage()),
            ]);
        }
    }
}
