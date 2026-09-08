<?php
/**
 * Hub Market — seed demo reviews and demo discounts.
 *
 * WHY THIS EXISTS
 * ---------------
 * The Figma reference draws every product card with a star rating and most with
 * a strike-through "was" price. This catalogue has neither at that density: of
 * 307 enabled, visible products, 140 carry a review and 13 carry a special
 * price. QA cycle 1 read the resulting gaps as missing UI on five separate
 * homepage sections. Seeding was an explicit client decision, taken with the
 * trade-off stated.
 *
 * WHY A SCRIPT PAIR AND NOT A DATA PATCH
 * --------------------------------------
 * A data patch is for changes that belong in every environment forever. This is
 * the opposite: demo content that must come out cleanly, on request, without a
 * schema version bump and without running on the next environment that is built
 * from this codebase. `unseed-demo-catalog-data.php` beside this file reverses
 * it exactly.
 *
 * HOW IT IS REVERSIBLE
 * --------------------
 *  - Reviews are tagged by NICKNAME. Every seeded review is written by
 *    "Hub Market Demo", which is also what the storefront displays — deliberately.
 *    Inventing customer names would make demo reviews indistinguishable from real
 *    ones both to a shopper and to whoever has to remove them later.
 *  - Prices are reversed from a MANIFEST, not by rule. The previous
 *    special_price of every product touched is written to demo-price-manifest.json
 *    before anything changes, including the products whose previous value was
 *    NULL. A rule ("delete every special price under X") would also delete real
 *    merchandising.
 *
 * TWO STORE GATES, BOTH REQUIRED
 * ------------------------------
 * A review needs a row in `review_store` AND an aggregated rating for the same
 * store, or the card shows a count with no stars. That has bitten this project
 * twice. Reviews are written to stores 0, 1 and 3 and aggregated per review.
 *
 * Usage:
 *   php8.4 dev/tools/hub-market/seed-demo-catalog-data.php [--reviews] [--prices] [--dry-run]
 *   (no flags = both)
 */
require dirname(__DIR__, 3) . '/app/bootstrap.php';

use Magento\Review\Model\Review;

const DEMO_NICKNAME = 'Hub Market Demo';
const RATING_ID     = 4;                 // the "Rating" rating; the only one with rating_store rows
const STORES        = [0, 1, 3];
const PRICE_SHARE   = 3;                 // seed a discount on every Nth eligible product
const MANIFEST      = __DIR__ . '/demo-price-manifest.json';

$argvFlags = array_slice($argv, 1);
$dryRun    = in_array('--dry-run', $argvFlags, true);
$doReviews = !$argvFlags || in_array('--reviews', $argvFlags, true) || $dryRun && !in_array('--prices', $argvFlags, true);
$doPrices  = !$argvFlags || in_array('--prices', $argvFlags, true) || $dryRun && !in_array('--reviews', $argvFlags, true);
if (in_array('--reviews', $argvFlags, true) && !in_array('--prices', $argvFlags, true)) { $doPrices = false; }
if (in_array('--prices', $argvFlags, true) && !in_array('--reviews', $argvFlags, true)) { $doReviews = false; }

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om        = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn     = $resource->getConnection();

/** Eligible = enabled and visible in catalog/search, at default scope. */
$eligible = static function () use ($conn, $resource): array {
    $attr = static function (string $code) use ($conn, $resource): int {
        return (int) $conn->fetchOne(
            $conn->select()->from(['a' => $resource->getTableName('eav_attribute')], ['attribute_id'])
                ->join(['t' => $resource->getTableName('eav_entity_type')],
                    't.entity_type_id = a.entity_type_id AND t.entity_type_code = "catalog_product"', [])
                ->where('a.attribute_code = ?', $code)
        );
    };
    $status = $attr('status');
    $vis    = $attr('visibility');

    return $conn->fetchCol(
        $conn->select()->from(['e' => $resource->getTableName('catalog_product_entity')], ['entity_id'])
            ->join(['st' => $resource->getTableName('catalog_product_entity_int')],
                "st.entity_id = e.entity_id AND st.attribute_id = {$status} AND st.store_id = 0 AND st.value = 1", [])
            ->join(['vi' => $resource->getTableName('catalog_product_entity_int')],
                "vi.entity_id = e.entity_id AND vi.attribute_id = {$vis} AND vi.store_id = 0 AND vi.value IN (2,4)", [])
            ->order('e.entity_id ASC')
    );
};

$ids = array_map('intval', $eligible());
printf("eligible products: %d\n", count($ids));

/* -------------------------------------------------------------- reviews -- */
if ($doReviews) {
    $reviewed = array_map('intval', $conn->fetchCol(
        $conn->select()->distinct()->from(
            ['r' => $resource->getTableName('review')], ['entity_pk_value']
        )->where('r.entity_id = ?', 1)
    ));
    $todo = array_values(array_diff($ids, $reviewed));
    printf("products without any review: %d\n", count($todo));

    $titles = [
        'Exactly as described', 'Good value', 'Arrived quickly', 'Happy with this',
        'Solid quality', 'Would buy again', 'Does the job', 'Better than expected',
    ];
    $bodies = [
        'Matches the description and arrived well packed.',
        'Good quality for the price. No complaints so far.',
        'Delivery was quick and the item was as pictured.',
        'Using it daily and it has held up well.',
        'Straightforward purchase, would order from this seller again.',
    ];

    $reviewFactory = $om->get(\Magento\Review\Model\ReviewFactory::class);
    $ratingFactory = $om->get(\Magento\Review\Model\RatingFactory::class);

    $options = $conn->fetchPairs(
        $conn->select()->from($resource->getTableName('rating_option'), ['value', 'option_id'])
            ->where('rating_id = ?', RATING_ID)
    );

    /*
     * Seeded, not random: the same catalogue must produce the same ratings on a
     * re-run, or an interrupted seed followed by a second one leaves two
     * different distributions in the same table.
     */
    mt_srand(20260827);

    $made = 0;
    foreach ($todo as $i => $productId) {
        $count = 1 + ($i % 3);                        // 1-3 reviews per product
        for ($n = 0; $n < $count; $n++) {
            $value = mt_rand(0, 9) < 7 ? 5 : 4;       // skewed high, never below 4
            if (mt_rand(0, 9) === 0) { $value = 3; }

            if ($dryRun) { $made++; continue; }

            $review = $reviewFactory->create();
            $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
                ->setEntityPkValue($productId)
                ->setStatusId(Review::STATUS_APPROVED)
                ->setTitle($titles[($i + $n) % count($titles)])
                ->setDetail($bodies[($i + $n) % count($bodies)])
                ->setNickname(DEMO_NICKNAME)
                ->setCustomerId(null)
                ->setStoreId(1)
                ->setStores(STORES)
                ->save();

            $ratingFactory->create()
                ->setRatingId(RATING_ID)
                ->setReviewId($review->getId())
                ->addOptionVote((int) $options[$value], $productId);

            $review->aggregate();
            $made++;
        }
        if (!$dryRun && $made % 100 === 0) { printf("  ... %d reviews\n", $made); }
    }
    printf("%s %d demo reviews across %d products\n", $dryRun ? 'WOULD create' : 'created', $made, count($todo));
}

/* --------------------------------------------------------------- prices -- */
if ($doPrices) {
    $priceAttr = (int) $conn->fetchOne(
        $conn->select()->from(['a' => $resource->getTableName('eav_attribute')], ['attribute_id'])
            ->join(['t' => $resource->getTableName('eav_entity_type')],
                't.entity_type_id = a.entity_type_id AND t.entity_type_code = "catalog_product"', [])
            ->where('a.attribute_code = ?', 'special_price')
    );
    $baseAttr = (int) $conn->fetchOne(
        $conn->select()->from(['a' => $resource->getTableName('eav_attribute')], ['attribute_id'])
            ->join(['t' => $resource->getTableName('eav_entity_type')],
                't.entity_type_id = a.entity_type_id AND t.entity_type_code = "catalog_product"', [])
            ->where('a.attribute_code = ?', 'price')
    );
    $table = $resource->getTableName('catalog_product_entity_decimal');

    $targets = [];
    foreach ($ids as $i => $id) {
        if ($i % PRICE_SHARE === 0) { $targets[] = $id; }
    }

    $prices = $conn->fetchPairs(
        $conn->select()->from($table, ['entity_id', 'value'])
            ->where('attribute_id = ?', $baseAttr)->where('store_id = 0')
            ->where('entity_id IN (?)', $targets)
    );
    $existing = $conn->fetchPairs(
        $conn->select()->from($table, ['entity_id', 'value'])
            ->where('attribute_id = ?', $priceAttr)->where('store_id = 0')
            ->where('entity_id IN (?)', $targets)
    );

    $manifest = ['attribute_id' => $priceAttr, 'store_id' => 0, 'previous' => []];
    $written  = 0;

    mt_srand(20260827);
    foreach ($targets as $id) {
        $base = (float) ($prices[$id] ?? 0);
        if ($base <= 0) { continue; }

        //  Never deepen an existing merchandised discount — those are real.
        if (array_key_exists($id, $existing) && $existing[$id] !== null) { continue; }

        $pct     = [10, 15, 20, 25][mt_rand(0, 3)];
        $special = round($base * (100 - $pct) / 100, 2);

        //  Record the PREVIOUS value, null included, before touching anything.
        $manifest['previous'][(string) $id] = $existing[$id] ?? null;

        if (!$dryRun) {
            $conn->insertOnDuplicate($table, [
                'attribute_id' => $priceAttr,
                'store_id'     => 0,
                'entity_id'    => $id,
                'value'        => $special,
            ], ['value']);
        }
        $written++;
    }

    if (!$dryRun) {
        file_put_contents(MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));
    }
    printf("%s %d demo special prices (manifest: %s)\n",
        $dryRun ? 'WOULD set' : 'set', $written, $dryRun ? '(not written)' : MANIFEST);
}

echo "done. Reindex and flush cache before checking the storefront.\n";
