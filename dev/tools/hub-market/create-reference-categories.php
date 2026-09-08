<?php
/**
 * Hub Market — create the two reference categories this catalogue lacks.
 *
 * The reference nav runs nine categories and the chip row eight; this catalogue
 * had neither Pharmacy nor FMCG, so the storefront rendered seven and six. The
 * nav map, chip map, order lists, icons and tints already name both url-keys —
 * this script creates the categories those slots are waiting for.
 *
 * PRODUCTS ARE ASSIGNED ADDITIVELY. A product can live in any number of
 * categories; nothing is moved out of super-market or health. FMCG takes the
 * supermarket staples (FMCG is packaged fast-moving goods — rice, oil, milk —
 * which is what that category holds), Pharmacy takes the personal-care items
 * from `health` (shaver, cosmetics, perfume). If merchandising later disagrees,
 * re-categorising in admin is normal catalogue work.
 *
 * REVERSIBLE: created category ids are written to a manifest; the companion
 * remove script deletes exactly those two categories, which also removes their
 * (additive) product assignments and nothing else.
 *
 * Store-scope names are set EXPLICITLY for store 1 (ar) and store 3 (en) —
 * this catalogue carries two opposing store-scope naming conventions, so
 * relying on the default-scope value alone renders the wrong language on one
 * storefront or the other.
 *
 * Usage: php8.4 dev/tools/hub-market/create-reference-categories.php [--dry-run]
 */
require dirname(__DIR__, 3) . '/app/bootstrap.php';

const MANIFEST = __DIR__ . '/reference-categories-manifest.json';

$dryRun = in_array('--dry-run', $argv, true);

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$categoryFactory = $om->get(\Magento\Catalog\Model\CategoryFactory::class);
$repo            = $om->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
$resource        = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn            = $resource->getConnection();

/*
 * Guard: url_key is unique per parent. If either key already exists at level 2
 * the category was created (by this script or by hand) and creating a twin
 * would 404 one of them.
 */
$existing = $conn->fetchPairs(
    $conn->select()
        ->from(['v' => $resource->getTableName('catalog_category_entity_varchar')], ['value', 'entity_id'])
        ->join(['a' => $resource->getTableName('eav_attribute')],
            'a.attribute_id = v.attribute_id AND a.attribute_code = "url_key" AND a.entity_type_id = 3', [])
        ->where('v.store_id = 0')
        ->where('v.value IN (?)', ['pharmacy', 'fmcg'])
);
if ($existing) {
    echo "already exist: ";
    foreach ($existing as $key => $id) { echo "$key(#$id) "; }
    echo "— nothing to do\n";
    exit(0);
}

/* Products, chosen by what they are, not by position. */
$fmcgIds = $conn->fetchCol(
    $conn->select()->from($resource->getTableName('catalog_category_product'), ['product_id'])
        ->where('category_id = ?', 101)                          // super-market staples
);
$pharmacyIds = $conn->fetchCol(
    $conn->select()
        ->from(['cp' => $resource->getTableName('catalog_category_product')], ['product_id'])
        ->join(['v' => $resource->getTableName('catalog_product_entity_varchar')],
            'v.entity_id = cp.product_id AND v.store_id = 0', [])
        ->join(['a' => $resource->getTableName('eav_attribute')],
            'a.attribute_id = v.attribute_id AND a.attribute_code = "name" AND a.entity_type_id = 4', [])
        ->where('cp.category_id = ?', 103)                       // health
        /*
         * The health category also holds six hoodies — legacy demo assignments,
         * not personal care. Pharmacy takes only what belongs in one.
         */
        ->where('v.value NOT LIKE ?', '%Hoodie%')
);

$defs = [
    ['url_key' => 'pharmacy', 'en' => 'Pharmacy', 'ar' => 'صيدلية',
     'position' => 2, 'products' => array_map('intval', $pharmacyIds)],
    ['url_key' => 'fmcg', 'en' => 'FMCG', 'ar' => 'سلع استهلاكية',
     'position' => 5, 'products' => array_map('intval', $fmcgIds)],
];

$manifest = ['created' => []];

foreach ($defs as $def) {
    printf("%-10s position %d, %d products%s\n",
        $def['url_key'], $def['position'], count($def['products']), $dryRun ? ' (dry run)' : '');
    if ($dryRun) {
        continue;
    }

    /* Default scope: English name (matches the older convention's level-2 rows
       that the storefront code reads at scope 0, e.g. Furniture, Fashion). */
    $category = $categoryFactory->create();
    /*
     * NO setPath(). Path must end with the category's OWN id (1/2/<id>) and the
     * id does not exist yet; setting '1/2' by hand is stored literally, which
     * makes the category claim to sit AT the root — the index then attributes
     * the whole catalogue to it (FMCG briefly reported 2220 products). The
     * resource model derives the correct path from parent_id on its own.
     */
    $category->setName($def['en'])
        ->setUrlKey($def['url_key'])
        ->setParentId(2)
        ->setPosition($def['position'])
        ->setIsActive(true)
        ->setIncludeInMenu(true)
        ->setCustomAttributes([]);
    $category->setStoreId(0);
    $saved = $repo->save($category);
    $id = (int) $saved->getId();

    /* Explicit per-store names — see the header. */
    foreach ([1 => $def['ar'], 3 => $def['en']] as $storeId => $name) {
        $store = $repo->get($id, $storeId);
        $store->setName($name);
        $repo->save($store);
    }

    /* Additive product assignment, straight rows — position 0 is fine. */
    if ($def['products']) {
        $rows = [];
        foreach ($def['products'] as $pid) {
            $rows[] = ['category_id' => $id, 'product_id' => $pid, 'position' => 0];
        }
        $conn->insertOnDuplicate($resource->getTableName('catalog_category_product'), $rows, ['position']);
    }

    $manifest['created'][] = ['id' => $id, 'url_key' => $def['url_key'], 'products' => count($def['products'])];
    printf("  created #%d\n", $id);
}

if (!$dryRun) {
    file_put_contents(MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));
    echo "manifest -> " . MANIFEST . "\n";
    echo "now run: indexer:reindex catalog_category_product catalog_product_category catalogsearch_fulltext; cache:flush\n";
}
