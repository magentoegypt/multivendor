<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Cart;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Every selection of one bundle: which option it belongs to and which product it adds.
 *
 * What BundleBuyRequestBuilder needs to validate the app's choices and to key
 * the configurable choices by child product id (the website's
 * super_attribute[<child id>]). One query. The selection table links the bundle
 * by the product link field (entity_id on Open Source, row_id with staging),
 * resolved through the metadata pool like core does.
 */
class BundleSelectionReader
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly MetadataPool $metadataPool
    ) {
    }

    /**
     * @return array<int, array{option_id: int, product_id: int, type_id: string}> selection id => selection
     */
    public function forBundle(ProductInterface $bundle): array
    {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $parentId = (int) $bundle->getData($linkField);
        if ($parentId < 1) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(
                    ['s' => $this->resource->getTableName('catalog_product_bundle_selection')],
                    ['selection_id', 'option_id', 'product_id']
                )
                ->join(
                    ['e' => $this->resource->getTableName('catalog_product_entity')],
                    'e.entity_id = s.product_id',
                    ['type_id']
                )
                ->where('s.parent_product_id = ?', $parentId)
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['selection_id']] = [
                'option_id' => (int) $row['option_id'],
                'product_id' => (int) $row['product_id'],
                'type_id' => (string) $row['type_id'],
            ];
        }

        return $out;
    }
}
