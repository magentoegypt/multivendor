<?php
/**
 * Hub Market — give every storefront-visible vendor a store avatar.
 *
 * QA cycle 1: "Display actual vendor profile images ... instead of the letter
 * avatar placeholders." No vendor has uploaded artwork, so the avatar is the
 * vendor's OWN first product photo — imagery that genuinely belongs to that
 * store, not stock. A vendor who later uploads a real logo in their panel
 * overwrites this the normal way, because this writes to the exact place the
 * panel writes: `general/store_information/logo` in ves_vendor_config, file
 * under pub/media/ves_vendors/logo/.
 *
 * Files are prefixed hm-auto- so the unseed can tell a seeded avatar from a
 * vendor's own upload and never delete the latter. A vendor whose config
 * already has a non-empty logo is skipped entirely.
 *
 * Usage: php8.4 dev/tools/hub-market/seed-vendor-logos.php [--dry-run]
 */
require dirname(__DIR__, 3) . '/app/bootstrap.php';

const MANIFEST = __DIR__ . '/vendor-logos-manifest.json';

$dryRun = in_array('--dry-run', $argv, true);
$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn = $resource->getConnection();

$mediaDir = BP . '/pub/media';
$logoDir  = $mediaDir . '/ves_vendors/logo';
if (!$dryRun && !is_dir($logoDir) && !mkdir($logoDir, 0775, true)) {
    fwrite(STDERR, "cannot create $logoDir\n");
    exit(1);
}

/* Every vendor a homepage rail can show: the curated featured set + approved. */
$vendorIds = array_map('intval', $conn->fetchCol(
    $conn->select()->from($resource->getTableName('ves_vendor_entity'), ['entity_id'])
        ->where('status = 1 OR vendor_id IN (?)', ['ENARA', 'ronza', 'loly', 'MIA'])
));

$existing = $conn->fetchPairs(
    $conn->select()->from($resource->getTableName('ves_vendor_config'), ['vendor_id', 'value'])
        ->where('path = ?', 'general/store_information/logo')
        ->where('vendor_id IN (?)', $vendorIds)
);

/* First product image per vendor, one query. */
$images = $conn->fetchPairs(
    $conn->select()
        ->from(['pe' => $resource->getTableName('catalog_product_entity')], ['vendor_id'])
        ->join(['v' => $resource->getTableName('catalog_product_entity_varchar')],
            'v.entity_id = pe.entity_id AND v.store_id = 0', ['img' => 'MIN(v.value)'])
        ->join(['a' => $resource->getTableName('eav_attribute')],
            'a.attribute_id = v.attribute_id AND a.attribute_code = "small_image" AND a.entity_type_id = 4', [])
        ->where('pe.vendor_id IN (?)', $vendorIds)
        ->where('v.value LIKE ?', '/%')
        ->group('pe.vendor_id')
);

$manifest = ['seeded' => []];
foreach ($vendorIds as $id) {
    if (!empty($existing[$id])) {
        printf("  #%-3d has a logo (%s) — skipped\n", $id, $existing[$id]);
        continue;
    }
    $src = $images[$id] ?? null;
    if (!$src) {
        printf("  #%-3d has no product imagery — left as letter disc\n", $id);
        continue;
    }
    $srcFile = $mediaDir . '/catalog/product' . $src;
    if (!is_file($srcFile)) {
        printf("  #%-3d source missing on disk (%s) — skipped\n", $id, $src);
        continue;
    }
    $ext  = strtolower(pathinfo($srcFile, PATHINFO_EXTENSION)) ?: 'jpg';
    $name = 'hm-auto-' . $id . '.' . $ext;

    printf("  #%-3d <- %s%s\n", $id, $src, $dryRun ? ' (dry run)' : '');
    if ($dryRun) {
        continue;
    }
    if (!copy($srcFile, $logoDir . '/' . $name)) {
        printf("  #%-3d copy FAILED\n", $id);
        continue;
    }
    $conn->insertOnDuplicate($resource->getTableName('ves_vendor_config'), [
        'vendor_id' => $id,
        'path'      => 'general/store_information/logo',
        'value'     => $name,
        'store_id'  => 0,
    ], ['value']);
    $manifest['seeded'][] = ['vendor_id' => $id, 'file' => $name];
}

if (!$dryRun) {
    file_put_contents(MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));
    echo "manifest -> " . MANIFEST . "\n";
}
