<?php
/**
 * Hub Market — three catalogue fixes for the search results filter rail.
 *
 * Found while fixing QA CL041-TC01 (ClickUp 86d45hdvn): with the results page
 * finally rendering its filter rail, two of the filters in it were wrong.
 *
 *   1. `closure_type` had its ATTRIBUTE CODE as its label. `frontend_label` was
 *      literally the string "closure_type", and the English store label row said
 *      the same, so English shoppers saw a raw developer identifier in the rail.
 *      Arabic was fine ("نوع الاغلاق") — which is why this survived: the store
 *      that had a real label was not the store anyone was checking.
 *
 *   2. `approval` is an INTERNAL vendor-workflow attribute — its options are
 *      "Pending New", "Pending Update", "Approved", "Unapproved" — and it was
 *      offering shoppers a filter over them. It carries is_filterable = 0, so it
 *      never appeared on category pages, but is_filterable_in_search = 1 put it
 *      on the SEARCH results rail only. That asymmetry is why it was never
 *      spotted on a category page.
 *
 *   3. `clothes_size` — the "size" filter on that page — carried the lowercase
 *      label "size". (The attribute actually CALLED `size` is a different one,
 *      already labelled "Size" and not filterable in search.)
 *
 * Goes through the model layer rather than raw SQL so the save runs the normal
 * path: caches invalidate, the indexer is told, and MagentoEgypt_SearchAttribute
 * Guard still gets to veto anything unsafe.
 *
 * Usage:
 *   php8.4 dev/tools/hub-market/fix-search-filter-attributes.php [--revert] [--dry-run]
 *
 * Re-runnable and reversible. After running, reindex:
 *   php8.4 bin/magento indexer:reindex catalogsearch_fulltext
 */
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../../app/bootstrap.php';

$revert = in_array('--revert', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

/** @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface $repo */
$repo = $om->get(\Magento\Catalog\Api\ProductAttributeRepositoryInterface::class);

/**
 * PER-STORE LABELS GO THROUGH `frontend_labels`, NOT `setStoreLabels()`.
 *
 * Two dead ends worth recording, because both look like they work:
 *
 *   1. ProductAttributeRepositoryInterface::save() writes `frontend_label` and
 *      leaves the per-store rows alone entirely. The default label changed, the
 *      storefront did not — a store-view label overrides the default, and the
 *      English row still held the raw code.
 *
 *   2. Calling setStoreLabels() and saving through the resource model also
 *      fails. Magento\Eav\Model\ResourceModel\Entity\Attribute has a private
 *      setStoreLabels($object, $frontendLabel) that runs during save: if the
 *      object carries `frontend_labels` as FrontendLabel objects — which every
 *      repository-loaded attribute does — it OVERWRITES store_labels from them.
 *      So the map gets silently replaced by the one already in the database.
 *
 * `frontend_labels` is therefore the authoritative property. _afterSave() then
 * deletes every eav_attribute_label row for the attribute and re-inserts from
 * the derived map, so the list below must be COMPLETE: every store that should
 * keep a label has to be in it, or that label is destroyed. Hence the merge.
 */
/** @var \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource */
$attributeResource = $om->get(\Magento\Catalog\Model\ResourceModel\Attribute::class);

/** @var \Magento\Eav\Api\Data\AttributeFrontendLabelInterfaceFactory $labelFactory */
$labelFactory = $om->get(\Magento\Eav\Api\Data\AttributeFrontendLabelInterfaceFactory::class);

/**
 * The label the English store should show, and the value it had before, so
 * --revert puts the exact prior state back rather than guessing.
 */
$plan = [
    //  Label was literally the attribute code, on the default AND on the
    //  English store view. Arabic already read "نوع الاغلاق", which is why this
    //  survived — the store that had a real label was not the one being checked.
    'closure_type' => [
        'label'        => $revert ? 'closure_type' : 'Closure Type',
        'label_stores' => [3],   // English store view; Arabic (1, 2) left alone
    ],

    //  The "size" filter on the results page is clothes_size, NOT the `size`
    //  attribute — that one is already labelled "Size" and is not filterable in
    //  search. clothes_size carried the lowercase code-ish label "size" on both
    //  the default and the English store view.
    'clothes_size' => [
        'label'        => $revert ? 'size' : 'Size',
        'label_stores' => [3],
    ],

    //  Internal vendor-workflow attribute — options are "Pending New",
    //  "Pending Update", "Approved", "Unapproved". is_filterable = 0 kept it off
    //  category pages, but is_filterable_in_search = 1 put it on the SEARCH
    //  rail only, which is why nobody caught it on a category page.
    'approval' => [
        'filterable_in_search' => $revert ? 1 : 0,
    ],
];

foreach ($plan as $code => $changes) {
    try {
        $attr = $repo->get($code);
    } catch (\Throwable $e) {
        printf("SKIP  %-14s not found\n", $code);
        continue;
    }

    $before = [
        'label'  => (string) $attr->getDefaultFrontendLabel(),
        'stores' => (array) $attr->getStoreLabels(),
        'fis'    => (int) $attr->getIsFilterableInSearch(),
    ];

    if (isset($changes['label'])) {
        $labels = $before['stores'];                     // keep every other store's label
        foreach ($changes['label_stores'] as $storeId) {
            $labels[(int) $storeId] = $changes['label'];
        }
        $attr->setDefaultFrontendLabel($changes['label']);

        $frontendLabels = [];
        foreach ($labels as $storeId => $text) {
            if ($text === null || $text === '') {
                continue;
            }
            $frontendLabels[] = $labelFactory->create()
                ->setStoreId((int) $storeId)
                ->setLabel((string) $text);
        }
        $attr->setFrontendLabels($frontendLabels);
    }
    if (isset($changes['filterable_in_search'])) {
        $attr->setIsFilterableInSearch($changes['filterable_in_search']);
    }

    if ($dryRun) {
        printf("DRY   %-14s label %-14s -> %-14s  store3 %-14s -> %-14s  fis %d -> %s\n",
            $code, $before['label'], $changes['label'] ?? '(same)',
            $before['stores'][3] ?? '(none)', $changes['label'] ?? '(same)',
            $before['fis'],
            isset($changes['filterable_in_search']) ? (string) $changes['filterable_in_search'] : '(same)');
        continue;
    }

    $attributeResource->save($attr);

    $after = $repo->get($code);
    $afterStores = (array) $after->getStoreLabels();
    printf("OK    %-14s label=%-14s store3=%-14s fis=%d   (was %s / %s / %d)\n",
        $code,
        (string) $after->getDefaultFrontendLabel(),
        $afterStores[3] ?? '(none)',
        (int) $after->getIsFilterableInSearch(),
        $before['label'], $before['stores'][3] ?? '(none)', $before['fis']);
}

echo $dryRun
    ? "\nDry run only — nothing written.\n"
    : "\nDone. Now run: php8.4 bin/magento indexer:reindex catalogsearch_fulltext catalog_product_attribute && php8.4 bin/magento cache:flush\n";
