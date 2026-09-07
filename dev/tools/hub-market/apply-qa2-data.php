<?php
/**
 * Hub Market — QA cycle 2 data changes (86d45m4tj), reversible.
 *
 * Three data fixes that belong together because one QA pass demanded them:
 *
 * 1. --labels  ENGLISH OPTION LABELS (store 3) for attribute options whose only
 *    label is Arabic at default scope. This is the root cause behind THREE
 *    filed symptoms: Arabic brand tiles on the English homepage (item 1-I),
 *    Arabic option values in the cart ("color: ارجواني", item 4) and in the
 *    checkout order summary (item 5-A). The map is curated by hand — these are
 *    transliterated global brand names and standard colours, not free text.
 *    Every inserted row is recorded in the manifest; removal restores the
 *    store-3 fallback exactly.
 *
 * 2. --bundle  A FOURTH DEMO BUNDLE. Item 1-F: "We need them to be made up of
 *    four cards". The catalog has three enabled bundles and four disabled
 *    half-configured test SKUs; a fourth real card needs a fourth real bundle.
 *    Seeded from four existing fitness products, tagged HM-DEMO- in the SKU,
 *    recorded in the manifest; ves_enable_order is set to 1 explicitly because
 *    products on this install are born unbuyable without it.
 *
 * 3. --cms     FOOTER BLOCKS, both stores. Item 1-L: the brand column gains
 *    the reference's "ACCEPTED PAYMENTS" caption between the country chips and
 *    the payment chips, and the Categories column swaps sprite icons for the
 *    reference's emoji. Previous content of each touched block is stored in
 *    the manifest before the first change.
 *
 * Usage:
 *   php8.4 dev/tools/hub-market/apply-qa2-data.php [--labels] [--bundle] [--cms] [--dry-run]
 *   (no flags = all three)
 *
 * Reversal:
 *   php8.4 dev/tools/hub-market/revert-qa2-data.php
 */
declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';

const MANIFEST = __DIR__ . '/qa2-data-manifest.json';

$flags    = array_slice($argv, 1);
$dryRun   = in_array('--dry-run', $flags, true);
$only     = array_values(array_intersect($flags, ['--labels', '--bundle', '--cms']));
$doLabels = !$only || in_array('--labels', $only, true);
$doBundle = !$only || in_array('--bundle', $only, true);
$doCms    = !$only || in_array('--cms', $only, true);

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om        = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn     = $resource->getConnection();

$manifest = is_file(MANIFEST) ? json_decode((string) file_get_contents(MANIFEST), true) : [];
$manifest = is_array($manifest) ? $manifest : [];

/* ---------------------------------------------------------------- labels */
if ($doLabels) {
    $maps = [
        'mgs_brand' => [
            'ابل' => 'Apple', 'سامسونج' => 'Samsung', 'شاومى' => 'Xiaomi', 'ال جى' => 'LG',
            'سونى' => 'Sony', 'كانون' => 'Canon', 'ميديا تك' => 'MediaTek', 'ويلسون' => 'Wilson',
            'هواوي' => 'Huawei', 'فريش' => 'Fresh', 'ايسر' => 'Acer', 'ديل' => 'Dell',
            'لينوڤو' => 'Lenovo', 'توشيبا' => 'Toshiba', 'راف باور' => 'RAVPower',
            'كيس لوجيك' => 'Case Logic', 'داهوا' => 'Dahua', 'هيك فيجن' => 'Hikvision',
            'ويستيرن ديجيتال' => 'Western Digital', 'نيتاك' => 'Netac', 'كيوكسيا' => 'KIOXIA',
            'سان ديسك' => 'SanDisk', 'كنجستون' => 'Kingston', 'بادجي' => 'Badgy',
            'تونر تانك' => 'Toner Tank', 'ايبسون' => 'Epson', 'باناسونيك' => 'Panasonic',
            'اتش بي' => 'HP',
        ],
        'color' => [
            'اسود' => 'Black', 'ازرق' => 'Blue', 'بني' => 'Brown', 'رمادي' => 'Grey',
            'اخضر' => 'Green', 'بنفسجي' => 'Purple', 'متعدد الالوان' => 'Multicolour',
            'برتقالي' => 'Orange', 'ارجواني' => 'Violet', 'احمر' => 'Red', 'ابيض' => 'White',
            'اصفر' => 'Yellow', 'بينك' => 'Pink', 'كحلي' => 'Navy', 'لبني' => 'Light Blue',
            'فوشيا' => 'Fuchsia', 'فيروزي' => 'Turquoise', 'بترولي' => 'Teal',
            'مشمشي' => 'Apricot', 'بادي روز' => 'Dusty Rose', 'زيتوني' => 'Olive',
            'زيتي' => 'Dark Olive', 'جملي' => 'Camel', 'عنابي' => 'Burgundy',
            'كافيه' => 'Coffee', 'اوف وايت' => 'Off-White', 'بيج' => 'Beige',
            'ذهبي' => 'Gold', 'فضي' => 'Silver', 'فستقي' => 'Pistachio',
            'اسود & رمادي' => 'Black & Grey',
        ],
        'clothes_size' => [
            'مقاس موحد' => 'One Size',
        ],
        'size' => [
            'مقاس واحد' => 'One Size',
        ],
    ];

    $inserted = $manifest['label_value_ids'] ?? [];
    $optTable = $resource->getTableName('eav_attribute_option_value');
    $count    = 0;

    foreach ($maps as $code => $map) {
        $rows = $conn->fetchAll(
            $conn->select()
                ->from(['o' => $resource->getTableName('eav_attribute_option')], ['option_id'])
                ->join(['a' => $resource->getTableName('eav_attribute')],
                    'a.attribute_id = o.attribute_id AND a.entity_type_id = 4', [])
                ->joinLeft(['v0' => $optTable], 'v0.option_id = o.option_id AND v0.store_id = 0', ['v0' => 'value'])
                ->joinLeft(['v3' => $optTable], 'v3.option_id = o.option_id AND v3.store_id = 3', ['v3' => 'value'])
                ->where('a.attribute_code = ?', $code)
        );

        foreach ($rows as $row) {
            $default = trim((string) $row['v0']);
            if ($default === '' || $row['v3'] !== null || !isset($map[$default])) {
                continue;
            }
            $count++;
            echo sprintf("[labels] %-12s option %-5d  %s -> %s\n", $code, $row['option_id'], $default, $map[$default]);
            if ($dryRun) {
                continue;
            }
            $conn->insert($optTable, [
                'option_id' => (int) $row['option_id'],
                'store_id'  => 3,
                'value'     => $map[$default],
            ]);
            $inserted[] = (int) $conn->lastInsertId($optTable);
        }
    }
    echo "[labels] {$count} store-3 labels " . ($dryRun ? 'would be added' : 'added') . "\n";
    $manifest['label_value_ids'] = $inserted;
}

/* ---------------------------------------------------------------- bundle */
if ($doBundle) {
    $sku = 'HM-DEMO-BUNDLE-FITNESS';
    $existing = $conn->fetchOne(
        $conn->select()->from($resource->getTableName('catalog_product_entity'), ['entity_id'])->where('sku = ?', $sku)
    );
    if ($existing) {
        echo "[bundle] {$sku} already exists (id {$existing}) — skipping\n";
    } else {
        /*
         * Children: four enabled, visible, in-stock simple products from the
         * sport/fitness range, each with an image. Chosen by name so the kit
         * reads as a kit; falls back to any four sport-ish products.
         */
        $childRows = $conn->fetchAll(
            "SELECT e.entity_id, e.sku, vn.value AS name, vi.value AS image
             FROM catalog_product_entity e
             JOIN catalog_product_entity_varchar vn ON vn.entity_id = e.entity_id AND vn.store_id = 0
               AND vn.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code='name' AND entity_type_id=4)
             JOIN catalog_product_entity_int st ON st.entity_id = e.entity_id AND st.store_id = 0 AND st.value = 1
               AND st.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code='status' AND entity_type_id=4)
             LEFT JOIN catalog_product_entity_varchar vi ON vi.entity_id = e.entity_id AND vi.store_id = 0
               AND vi.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code='image' AND entity_type_id=4)
             WHERE e.type_id = 'simple'
               AND (vn.value LIKE '%Yoga%' OR vn.value LIKE '%Fitness%' OR vn.value LIKE '%Gym%'
                    OR vn.value LIKE '%Sport%' OR vn.value LIKE '%Ball%' OR vn.value LIKE '%Dumbbell%')
               AND vi.value IS NOT NULL AND vi.value <> 'no_selection'
             ORDER BY e.entity_id ASC
             LIMIT 4"
        );

        if (count($childRows) < 4) {
            echo "[bundle] only " . count($childRows) . " fitness children found — aborting bundle seed\n";
        } else {
            echo "[bundle] children:\n";
            foreach ($childRows as $c) {
                echo "  - {$c['sku']}  {$c['name']}\n";
            }
            if (!$dryRun) {
                $vendorId = (int) $conn->fetchOne(
                    "SELECT vv.value FROM catalog_product_entity_int vv
                     WHERE vv.entity_id = " . (int) $childRows[0]['entity_id'] . " AND vv.store_id = 0
                       AND vv.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code='vendor_id' AND entity_type_id=4)"
                );

                /** @var \Magento\Catalog\Model\Product $product */
                $product = $om->create(\Magento\Catalog\Model\Product::class);
                $product->setTypeId('bundle')
                    ->setAttributeSetId(4)
                    ->setSku($sku)
                    ->setName('Home Fitness Starter Pack')
                    ->setDescription('Everything you need to start training at home — four essentials from one seller, bundled at a saving over buying them one by one.')
                    ->setShortDescription('Four home-workout essentials in one box.')
                    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
                    ->setWebsiteIds([1])
                    ->setPriceType(\Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC)
                    ->setPriceView(0)
                    ->setSkuType(0)
                    ->setWeightType(0)
                    ->setShipmentType(0)
                    ->setSpecialPrice(85)   // dynamic-price bundle: 85 = 15% off the sum
                    ->setImage($childRows[0]['image'])
                    ->setSmallImage($childRows[0]['image'])
                    ->setThumbnail($childRows[0]['image'])
                    ->setStockData(['use_config_manage_stock' => 0, 'manage_stock' => 0, 'is_in_stock' => 1]);
                if ($vendorId) {
                    $product->setData('vendor_id', $vendorId);
                }
                /* Products on this install are born unbuyable without this — see
                   the ves_enable_order history. */
                $product->setData('ves_enable_order', 1);

                $option = $om->create(\Magento\Bundle\Api\Data\OptionInterfaceFactory::class)->create();
                $option->setTitle("What's inside")
                    ->setDefaultTitle("What's inside")
                    ->setType('checkbox')
                    ->setRequired(true)
                    ->setPosition(1);

                $links = [];
                $pos = 0;
                foreach ($childRows as $c) {
                    $link = $om->create(\Magento\Bundle\Api\Data\LinkInterfaceFactory::class)->create();
                    $link->setSku($c['sku'])
                        ->setQty(1)
                        ->setIsDefault(true)
                        ->setCanChangeQuantity(0)
                        ->setPosition(++$pos)
                        ->setPriceType(0)
                        ->setPrice(0.0);
                    $links[] = $link;
                }
                $option->setProductLinks($links);

                $ext = $product->getExtensionAttributes();
                $ext->setBundleProductOptions([$option]);
                $product->setExtensionAttributes($ext);

                $saved = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class)->save($product);
                $manifest['bundle_product_id'] = (int) $saved->getId();
                $manifest['bundle_sku']        = $sku;
                echo "[bundle] created {$sku} (id {$saved->getId()})\n";

                /* Arabic store name, so the AR homepage reads naturally. */
                $conn->insert($resource->getTableName('catalog_product_entity_varchar'), [
                    'attribute_id' => (int) $conn->fetchOne("SELECT attribute_id FROM eav_attribute WHERE attribute_code='name' AND entity_type_id=4"),
                    'store_id'     => 1,
                    'entity_id'    => (int) $saved->getId(),
                    'value'        => 'باقة اللياقة المنزلية',
                ]);
            }
        }
    }
}

/* ------------------------------------------------------------------- cms */
if ($doCms) {
    $blocks = [
        // identifier => [store_id => new content]
        'hm_footer_brand' => [
            3 => '<p>Multi-vendor marketplace. Arabic-ready, VAT-compliant, mobile-first.</p>'
                . '<div class="hm-footer__chips"><span class="hm-footer__chip">Egypt</span></div>'
                . '<p class="hm-footer__pay-label">Accepted Payments</p>'
                . '<div class="hm-footer__chips"><span class="hm-footer__chip">Visa</span>'
                . '<span class="hm-footer__chip">Mastercard</span>'
                . '<span class="hm-footer__chip">Cash on Delivery</span></div>',
            1 => '<p>سوق متعدد البائعين. يدعم العربية، متوافق مع الضريبة، مصمم للجوال أولاً.</p>'
                . '<div class="hm-footer__chips"><span class="hm-footer__chip">مصر</span></div>'
                . '<p class="hm-footer__pay-label">طرق الدفع المقبولة</p>'
                . '<div class="hm-footer__chips"><span class="hm-footer__chip">فيزا</span>'
                . '<span class="hm-footer__chip">ماستركارد</span>'
                . '<span class="hm-footer__chip">الدفع عند الاستلام</span></div>',
        ],
        'hm_footer_categories' => [
            3 => "<h3>Categories</h3>\n<ul class=\"hm-footer__cats\">\n"
                . "  <li><a href=\"{{store url=\"super-market.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">🛒</span><span>Grocery</span></a></li>\n"
                . "  <li><a href=\"{{store url=\"pharmacy.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">💊</span><span>Pharmacy</span></a></li>\n"
                . "  <li><a href=\"{{store url=\"furniture.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">🛋️</span><span>Furniture</span></a></li>\n"
                . "  <li><a href=\"{{store url=\"clothes.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">👗</span><span>Fashion</span></a></li>\n"
                . "  <li><a href=\"{{store url=\"fmcg.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">📦</span><span>FMCG</span></a></li>\n</ul>\n",
            1 => "<h3>الفئات</h3>\n<ul class=\"hm-footer__cats\">\n"
                . "  <li><a href=\"{{store url=\"super-market.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">🛒</span><span>البقالة</span></a></li>\n"
                . "  <li><a href=\"{{store url=\"pharmacy.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">💊</span><span>الصيدلية</span></a></li>\n"
                . "  <li><a href=\"{{store url=\"furniture.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">🛋️</span><span>الأثاث</span></a></li>\n"
                . "  <li><a href=\"{{store url=\"clothes.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">👗</span><span>الأزياء</span></a></li>\n"
                . "  <li><a href=\"{{store url=\"fmcg.html\"}}\"><span class=\"hm-footer__cat-emoji\" aria-hidden=\"true\">📦</span><span>السلع الاستهلاكية</span></a></li>\n</ul>\n",
        ],
    ];

    $backups = $manifest['cms_backups'] ?? [];
    foreach ($blocks as $identifier => $stores) {
        foreach ($stores as $storeId => $content) {
            $row = $conn->fetchRow(
                $conn->select()
                    ->from(['b' => $resource->getTableName('cms_block')], ['block_id', 'content'])
                    ->join(['s' => $resource->getTableName('cms_block_store')], 's.block_id = b.block_id', [])
                    ->where('b.identifier = ?', $identifier)
                    ->where('s.store_id = ?', $storeId)
            );
            if (!$row) {
                echo "[cms] {$identifier} store {$storeId}: NOT FOUND — skipped\n";
                continue;
            }
            $key = "{$identifier}:{$storeId}";
            echo "[cms] {$identifier} store {$storeId}: " . strlen((string) $row['content']) . " -> " . strlen($content) . " chars\n";
            if ($dryRun) {
                continue;
            }
            if (!isset($backups[$key])) {
                $backups[$key] = ['block_id' => (int) $row['block_id'], 'content' => (string) $row['content']];
            }
            $conn->update(
                $resource->getTableName('cms_block'),
                ['content' => $content],
                ['block_id = ?' => (int) $row['block_id']]
            );
        }
    }
    $manifest['cms_backups'] = $backups;
}

/* ----------------------------------------------------------------- deals */
/*
 * Item 1-B boxed the reference's "04:54:40 remaining" countdown as missing.
 * The countdown block already exists and is honest: it derives its deadline
 * from the earliest real special_to_date among live deals and renders nothing
 * without one — and only ONE product in the catalog carries a special_to_date.
 * The seeded demo specials get a real end date (the last day of this month,
 * store time), which is both a genuine deadline for the countdown and a
 * truthful statement about the demo prices. Runs with --cms. Every row was
 * NULL before (verified below), so the revert simply deletes the rows.
 */
if ($doCms) {
    $spAttr = (int) $conn->fetchOne("SELECT attribute_id FROM {$resource->getTableName('eav_attribute')} WHERE attribute_code='special_price' AND entity_type_id=4");
    $tdAttr = (int) $conn->fetchOne("SELECT attribute_id FROM {$resource->getTableName('eav_attribute')} WHERE attribute_code='special_to_date' AND entity_type_id=4");
    $dec    = $resource->getTableName('catalog_product_entity_decimal');
    $dtT    = $resource->getTableName('catalog_product_entity_datetime');

    $ids = $conn->fetchCol(
        "SELECT sp.entity_id FROM {$dec} sp
         LEFT JOIN {$dtT} td ON td.entity_id = sp.entity_id AND td.attribute_id = {$tdAttr} AND td.store_id = 0
         WHERE sp.attribute_id = {$spAttr} AND sp.store_id = 0 AND sp.value IS NOT NULL AND td.value_id IS NULL"
    );
    $endOfMonth = (new DateTimeImmutable('last day of this month'))->format('Y-m-d 23:59:59');
    echo '[deals] ' . count($ids) . " products get special_to_date = {$endOfMonth}\n";
    if (!$dryRun && $ids) {
        foreach ($ids as $id) {
            $conn->insert($dtT, [
                'attribute_id' => $tdAttr,
                'store_id'     => 0,
                'entity_id'    => (int) $id,
                'value'        => $endOfMonth,
            ]);
        }
        $manifest['special_to_date_entity_ids'] = array_map('intval', $ids);
        $manifest['special_to_date_attr']       = $tdAttr;
    }
}

/* ----------------------------------------------------------------- brand */
/*
 * Item 1-A: every remaining "MECommerce" in CMS prose becomes "Hub Market".
 * Runs with --cms (they are the same concern). Backed up per page row.
 */
if ($doCms) {
    $pages = $conn->fetchAll(
        $conn->select()->from($resource->getTableName('cms_page'), ['page_id', 'identifier', 'content'])
            ->where('content LIKE ?', '%MECommerce%')
    );
    $pageBackups = $manifest['cms_page_backups'] ?? [];
    foreach ($pages as $p) {
        echo "[brand] cms_page {$p['identifier']} (id {$p['page_id']}): "
            . substr_count($p['content'], 'MECommerce') . " occurrence(s)\n";
        if ($dryRun) {
            continue;
        }
        if (!isset($pageBackups[$p['page_id']])) {
            $pageBackups[$p['page_id']] = $p['content'];
        }
        $conn->update(
            $resource->getTableName('cms_page'),
            ['content' => str_replace('MECommerce', 'Hub Market', $p['content'])],
            ['page_id = ?' => (int) $p['page_id']]
        );
    }
    $manifest['cms_page_backups'] = $pageBackups;
}

if (!$dryRun) {
    file_put_contents(MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "manifest written: " . MANIFEST . "\n";
}
echo $dryRun ? "DRY RUN — nothing written\n" : "done\n";
