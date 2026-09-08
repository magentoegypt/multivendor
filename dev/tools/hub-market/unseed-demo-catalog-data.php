<?php
/**
 * Hub Market — remove the demo reviews and demo discounts.
 *
 * The exact reverse of seed-demo-catalog-data.php beside it. Run it and the
 * catalogue is back to what it was: 140 genuinely reviewed products and 13
 * genuinely discounted ones.
 *
 * REVIEWS are matched on nickname, which no real customer carries. Deleting the
 * `review` row cascades to review_detail, review_store and rating_option_vote by
 * foreign key; the per-product summary is then re-aggregated so the star counts
 * on the storefront come down with them rather than being left stale.
 *
 * PRICES are restored from demo-price-manifest.json, product by product, to the
 * value each one held before the seed — including the ones that held NULL, which
 * are deleted rather than zeroed. A zero would read as a free product.
 *
 * A missing manifest is not an error worth stopping for: it means the price half
 * was never run, or was already reversed.
 *
 * Usage:
 *   php8.4 dev/tools/hub-market/unseed-demo-catalog-data.php [--reviews] [--prices] [--dry-run]
 *   (no flags = both)
 */
require dirname(__DIR__, 3) . '/app/bootstrap.php';

const DEMO_NICKNAME = 'Hub Market Demo';
const MANIFEST      = __DIR__ . '/demo-price-manifest.json';

$flags     = array_slice($argv, 1);
$dryRun    = in_array('--dry-run', $flags, true);
$only      = array_values(array_filter($flags, static fn ($f) => in_array($f, ['--reviews', '--prices'], true)));
$doReviews = !$only || in_array('--reviews', $only, true);
$doPrices  = !$only || in_array('--prices', $only, true);

$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn     = $resource->getConnection();

/* -------------------------------------------------------------- reviews -- */
if ($doReviews) {
    $rows = $conn->fetchAll(
        $conn->select()
            ->from(['r' => $resource->getTableName('review')], ['review_id', 'entity_pk_value'])
            ->join(['d' => $resource->getTableName('review_detail')], 'd.review_id = r.review_id', [])
            ->where('d.nickname = ?', DEMO_NICKNAME)
    );
    $ids      = array_map(static fn ($r) => (int) $r['review_id'], $rows);
    $products = array_values(array_unique(array_map(static fn ($r) => (int) $r['entity_pk_value'], $rows)));

    printf("%s %d demo reviews across %d products\n",
        $dryRun ? 'WOULD delete' : 'deleting', count($ids), count($products));

    if ($ids && !$dryRun) {
        foreach (array_chunk($ids, 500) as $chunk) {
            $conn->delete($resource->getTableName('review'), ['review_id IN (?)' => $chunk]);
        }
        //  Re-aggregate so the storefront's counts and stars drop with the rows.
        //  Done through the resource model rather than by deleting summary rows:
        //  a product that ALSO has genuine reviews must keep them and be
        //  recalculated, not blanked.
        $reviewResource = $om->get(\Magento\Review\Model\ResourceModel\Review::class);
        foreach ($products as $productId) {
            $reviewResource->aggregate(
                $om->get(\Magento\Review\Model\ReviewFactory::class)->create()
                   ->setEntityPkValue($productId)
                   ->setEntityId(1)
            );
        }
        echo "  re-aggregated summaries\n";
    }
}

/* --------------------------------------------------------------- prices -- */
if ($doPrices) {
    if (!is_file(MANIFEST)) {
        echo "no price manifest — nothing to restore\n";
    } else {
        $manifest = json_decode((string) file_get_contents(MANIFEST), true);
        $table    = $resource->getTableName('catalog_product_entity_decimal');
        $attr     = (int) $manifest['attribute_id'];
        $store    = (int) $manifest['store_id'];
        $restored = 0;
        $cleared  = 0;

        foreach ($manifest['previous'] as $id => $value) {
            $id = (int) $id;
            if ($value === null) {
                //  DELETE, not set-to-zero: this product had no special price.
                if (!$dryRun) {
                    $conn->delete($table, [
                        'attribute_id = ?' => $attr,
                        'store_id = ?'     => $store,
                        'entity_id = ?'    => $id,
                    ]);
                }
                $cleared++;
            } else {
                if (!$dryRun) {
                    $conn->insertOnDuplicate($table, [
                        'attribute_id' => $attr,
                        'store_id'     => $store,
                        'entity_id'    => $id,
                        'value'        => $value,
                    ], ['value']);
                }
                $restored++;
            }
        }
        printf("%s %d prices cleared, %d restored to a previous value\n",
            $dryRun ? 'WOULD restore:' : 'restored:', $cleared, $restored);

        if (!$dryRun) {
            rename(MANIFEST, MANIFEST . '.applied');
            echo "  manifest retired to " . basename(MANIFEST) . ".applied\n";
        }
    }
}

echo "done. Reindex and flush cache.\n";
