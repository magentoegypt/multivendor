<?php
/**
 * Reverse of create-reference-categories.php: deletes exactly the categories
 * that script created, by id from its manifest. Deleting a category removes its
 * catalog_category_product rows with it, and every product assigned there was
 * assigned ADDITIVELY — each one keeps its original categories untouched.
 *
 * Usage: php8.4 dev/tools/hub-market/remove-reference-categories.php [--dry-run]
 */
require dirname(__DIR__, 3) . '/app/bootstrap.php';

const MANIFEST = __DIR__ . '/reference-categories-manifest.json';

$dryRun = in_array('--dry-run', $argv, true);

if (!is_file(MANIFEST)) {
    echo "no manifest — nothing to remove\n";
    exit(0);
}

$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
/* Category delete is gated behind the admin's "secure area" flag on purpose. */
$om->get(\Magento\Framework\Registry::class)->register('isSecureArea', true);

$repo = $om->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
$manifest = json_decode((string) file_get_contents(MANIFEST), true);

foreach ($manifest['created'] ?? [] as $row) {
    printf("%s #%d (%s)\n", $dryRun ? 'WOULD delete' : 'deleting', $row['id'], $row['url_key']);
    if (!$dryRun) {
        try {
            $repo->deleteByIdentifier((int) $row['id']);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            echo "  already gone\n";
        }
    }
}
if (!$dryRun) {
    rename(MANIFEST, MANIFEST . '.applied');
    echo "manifest retired; reindex + cache:flush to finish\n";
}
