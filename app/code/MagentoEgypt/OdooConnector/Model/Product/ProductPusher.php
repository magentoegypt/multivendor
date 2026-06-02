<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;

/**
 * Shared "push one product to Odoo" service used by both the CLI command and
 * the queue consumer. Idempotent: an existing odoo_id triggers a write, never
 * a second create.
 */
class ProductPusher
{
    private const ENTITY_TYPE = 'product';
    private const ODOO_MODEL = 'product.template';

    private ProductRepositoryInterface $productRepository;
    private OdooClient $odooClient;
    private ProductPushMapper $pushMapper;
    private MapManager $mapManager;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        OdooClient $odooClient,
        ProductPushMapper $pushMapper,
        MapManager $mapManager
    ) {
        $this->productRepository = $productRepository;
        $this->odooClient = $odooClient;
        $this->pushMapper = $pushMapper;
        $this->mapManager = $mapManager;
    }

    /**
     * @return array{action: string, odoo_id: int, sku: string}
     */
    public function pushById(int $magentoId, string $correlationId): array
    {
        return $this->push($this->productRepository->getById($magentoId), $correlationId);
    }

    /**
     * @return array{action: string, odoo_id: int, sku: string}
     */
    public function push(ProductInterface $product, string $correlationId): array
    {
        $sku = (string)$product->getSku();
        $values = $this->pushMapper->toOdooValues($product);

        $map = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $sku, 0);
        $existingOdooId = ($map !== null && $map->getData('odoo_id')) ? (int)$map->getData('odoo_id') : null;

        // Re-attach to an existing Odoo product by SKU when the map has no link
        // (idempotent vs Odoo even after the entity map is cleared — never duplicates).
        if ($existingOdooId === null) {
            $found = $this->odooClient->executeKw(self::ODOO_MODEL, 'search', [[['default_code', '=', $sku]]], ['limit' => 1]);
            if (is_array($found) && isset($found[0])) {
                $existingOdooId = (int)$found[0];
            }
        }

        if ($existingOdooId !== null) {
            $this->odooClient->executeKw(self::ODOO_MODEL, 'write', [[$existingOdooId], $values]);
            $odooId = $existingOdooId;
            $action = 'update';
        } else {
            $odooId = (int)$this->odooClient->executeKw(self::ODOO_MODEL, 'create', [$values]);
            $action = 'create';
        }

        $checksum = $this->pushMapper->expectedOdooChecksum($values);
        $this->mapManager->link([
            'entity_type' => self::ENTITY_TYPE,
            'magento_natural_key' => $sku,
            'magento_id' => (string)$product->getId(),
            'odoo_model' => self::ODOO_MODEL,
            'odoo_id' => $odooId,
            'magento_checksum' => $checksum,
            'odoo_checksum' => $checksum,
            'last_direction' => EntityMap::DIRECTION_M2O,
            'sync_status' => EntityMap::STATUS_LINKED,
            'website_id' => 0,
            'last_correlation_id' => $correlationId,
        ]);

        return ['action' => $action, 'odoo_id' => $odooId, 'sku' => $sku];
    }
}
