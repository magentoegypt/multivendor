<?php
/**
 * Hub Market: regression check for the order item "Discount Amount" column (ClickUp CL036-TC28, 86d4bjum9).
 *
 *   php8.4 dev/tools/hub-market/order-discount-column-check.php
 *
 * Renders the column the way each order view does, for the newest order item of three kinds: special/catalog price
 * only, cart-rule discount only, and both. Expected: cart-rule discount + (original price − sold price) × qty.
 *   - admin order view and admin Marketplace order view: Magento's renderer (core template and VendorExtend's);
 *   - seller panel: the Vnecoms vendor renderer and template (vendors area).
 * Read-only: nothing is saved; also checks sales_order_item.discount_amount still holds the cart-rule amount only.
 * Exit code 0 = all passed, 1 = a check failed.
 */
use Magento\Framework\App\Bootstrap;

require dirname(__DIR__, 3) . '/app/bootstrap.php';

$area = $argv[1] ?? null;
if ($area === null) {   // run each area in its own process (the area code can be set once per process)
    $failed = 0;
    foreach (['adminhtml', 'vendors'] as $a) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $a, $code);
        $failed += $code ? 1 : 0;
    }
    echo $failed ? "\nSome checks FAILED\n" : "\nAll checks passed\n";
    exit($failed ? 1 : 0);
}

$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode($area);
$om->get(\Magento\Framework\App\AreaList::class)->getArea($area)->load(\Magento\Framework\App\Area::PART_CONFIG);
$om->get(\Magento\Framework\View\DesignInterface::class)->setDefaultDesignTheme();
$db = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$layout = $om->get(\Magento\Framework\View\LayoutInterface::class);

$kinds = [
    'special price only' => 'i.original_price > i.price AND i.discount_amount = 0',
    'cart rule only' => 'i.original_price <= i.price AND i.discount_amount > 0',
    'special price + cart rule' => 'i.original_price > i.price AND i.discount_amount > 0',
];
$views = $area === 'adminhtml'
    ? [
        'admin order view (core template)' => [\Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer::class, 'Magento_Sales::order/view/items/renderer/default.phtml'],
        'admin order views (VendorExtend template)' => [\Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer::class, 'MagentoEgypt_VendorExtend::order/view/items/renderer/default.phtml'],
    ]
    : [
        'seller panel (Vnecoms template)' => [\Vnecoms\VendorsSales\Block\Vendors\Order\View\Items\Renderer\DefaultRenderer::class, 'Vnecoms_VendorsSales::order/view/items/renderer/default.phtml'],
    ];

$failures = 0;
foreach ($kinds as $kind => $where) {
    $row = $db->fetchRow("SELECT i.item_id, i.order_id FROM sales_order_item i WHERE i.parent_item_id IS NULL AND $where ORDER BY i.item_id DESC LIMIT 1");
    if (!$row) {
        echo "SKIP $kind: no such order item\n";
        continue;
    }
    $order = $om->create(\Magento\Sales\Model\Order::class)->load($row['order_id']);
    $item = $order->getItemById($row['item_id']);
    $expected = (float) $item->getDiscountAmount()
        + max(0.0, ((float) $item->getOriginalPrice() - (float) $item->getPrice()) * (float) $item->getQtyOrdered());
    $want = trim(strip_tags($order->formatPrice($expected)));
    foreach ($views as $view => [$class, $template]) {
        $block = $layout->createBlock($class);
        $block->setTemplate($template)->setItem($item)->setOrder($order)->setColumns(['discont' => 'col-discont']);
        try {
            $html = $block->toHtml();
            preg_match('#col-discont[^>]*>(.*?)</td>#s', $html, $m);
            $got = trim(preg_replace('/\s+/', ' ', strip_tags($m[1] ?? '(no Discount Amount cell)')));
        } catch (\Throwable $e) {
            $got = get_class($e) . ': ' . $e->getMessage();
        }
        $ok = $got === $want || str_starts_with($got, $want . ' ') ;
        printf("%s %-24s #%s %-42s shows %s, expected %s\n", $ok ? 'PASS' : 'FAIL', $kind, $order->getIncrementId(), $view, $got, $want);
        $failures += $ok ? 0 : 1;
    }
    $stored = (float) $db->fetchOne('SELECT discount_amount FROM sales_order_item WHERE item_id = ?', [$row['item_id']]);
    $okStored = abs($stored - (float) $item->getDiscountAmount()) < 0.0001;
    printf("%s %-24s #%s stored discount_amount is the cart-rule amount only (%.2f)\n", $okStored ? 'PASS' : 'FAIL', $kind, $order->getIncrementId(), $stored);
    $failures += $okStored ? 0 : 1;
}
exit($failures ? 1 : 0);
