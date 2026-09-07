<?php
/**
 * Hub Market — reverse apply-qa2-data.php exactly, from its manifest.
 *
 *  - label rows:   deletes the recorded eav_attribute_option_value ids, which
 *                  restores the store-3 fallback to the default (Arabic) label.
 *  - demo bundle:  deletes the recorded product id (registry secure-area gate
 *                  applies, same as any programmatic delete).
 *  - cms blocks:   restores the recorded previous content byte-for-byte.
 *
 * Usage: php8.4 dev/tools/hub-market/revert-qa2-data.php [--dry-run]
 */
declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';

const MANIFEST = __DIR__ . '/qa2-data-manifest.json';

$dryRun = in_array('--dry-run', array_slice($argv, 1), true);

if (!is_file(MANIFEST)) {
    fwrite(STDERR, "no manifest at " . MANIFEST . " — nothing to revert\n");
    exit(1);
}
$manifest = json_decode((string) file_get_contents(MANIFEST), true) ?: [];

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om        = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn     = $resource->getConnection();

if (!empty($manifest['label_value_ids'])) {
    $ids = array_map('intval', $manifest['label_value_ids']);
    echo "[labels] deleting " . count($ids) . " store-3 label rows\n";
    if (!$dryRun) {
        $conn->delete(
            $resource->getTableName('eav_attribute_option_value'),
            ['value_id IN (?)' => $ids, 'store_id = ?' => 3]
        );
        unset($manifest['label_value_ids']);
    }
}

if (!empty($manifest['bundle_product_id'])) {
    $id = (int) $manifest['bundle_product_id'];
    echo "[bundle] deleting product id {$id} ({$manifest['bundle_sku']})\n";
    if (!$dryRun) {
        $om->get(\Magento\Framework\Registry::class)->register('isSecureArea', true, true);
        try {
            $repo = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
            $repo->deleteById((string) $manifest['bundle_sku']);
            unset($manifest['bundle_product_id'], $manifest['bundle_sku']);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            echo "[bundle] already gone\n";
            unset($manifest['bundle_product_id'], $manifest['bundle_sku']);
        }
    }
}

if (!empty($manifest['hero_backups'])) {
    foreach ($manifest['hero_backups'] as $bannerId => $fields) {
        echo "[hero] restoring banner {$bannerId}: " . json_encode($fields, JSON_UNESCAPED_UNICODE) . "\n";
        if (!$dryRun) {
            $conn->update(
                $resource->getTableName('magentoegypt_hero_banner'),
                $fields,
                ['banner_id = ?' => (int) $bannerId]
            );
        }
    }
    if (!$dryRun) {
        unset($manifest['hero_backups']);
    }
}

if (!empty($manifest['special_to_date_entity_ids'])) {
    $ids = array_map('intval', $manifest['special_to_date_entity_ids']);
    echo "[deals] deleting special_to_date on " . count($ids) . " products (was NULL)\n";
    if (!$dryRun) {
        $conn->delete(
            $resource->getTableName('catalog_product_entity_datetime'),
            [
                'attribute_id = ?' => (int) $manifest['special_to_date_attr'],
                'store_id = ?'     => 0,
                'entity_id IN (?)' => $ids,
            ]
        );
        unset($manifest['special_to_date_entity_ids'], $manifest['special_to_date_attr']);
    }
}

if (!empty($manifest['cms_page_backups'])) {
    foreach ($manifest['cms_page_backups'] as $pageId => $content) {
        echo "[brand] restoring cms_page {$pageId}\n";
        if (!$dryRun) {
            $conn->update(
                $resource->getTableName('cms_page'),
                ['content' => $content],
                ['page_id = ?' => (int) $pageId]
            );
        }
    }
    if (!$dryRun) {
        unset($manifest['cms_page_backups']);
    }
}

if (!empty($manifest['cms_backups'])) {
    foreach ($manifest['cms_backups'] as $key => $backup) {
        echo "[cms] restoring {$key} (" . strlen($backup['content']) . " chars)\n";
        if (!$dryRun) {
            $conn->update(
                $resource->getTableName('cms_block'),
                ['content' => $backup['content']],
                ['block_id = ?' => (int) $backup['block_id']]
            );
        }
    }
    if (!$dryRun) {
        unset($manifest['cms_backups']);
    }
}

if (!$dryRun) {
    file_put_contents(MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
echo $dryRun ? "DRY RUN — nothing changed\n" : "reverted\n";
