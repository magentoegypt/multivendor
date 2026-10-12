<?php
/**
 * Hub Market: regression check for pictures and videos in the product page summary (ClickUp TC103 14zb93nx97e).
 *
 *   php8.4 dev/tools/hub-market/pdp-summary-media-check.php [template]
 *
 * Renders the hub-market `Magento_Catalog::product/view/summary.phtml` (or the template file given) directly, with a
 * stand-in block, so nothing is saved and no page cache is touched. Exit code 0 = all passed, 1 = a check failed.
 *
 * What it covers:
 *  1. QA's product 2546 on both stores: the two text lines AND the picture from `{{media url=…}}`, pointing at a file
 *     that exists under pub/media.
 *  2. Made-up short descriptions: video with <source>, audio, YouTube/Vimeo iframes, escaped markup, unquoted media
 *     directive; and what must NOT pass — a javascript:/other-site iframe src, event handlers, a widget directive,
 *     a forged placeholder.
 *  3. Every stored short_description (all store views) renders without a PHP warning, and every picture/video in it
 *     with a usable src reaches the page.
 */
use Magento\Framework\App\Bootstrap;

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('frontend');

$template = $argv[1] ?? BP . '/app/design/frontend/MagentoEgypt/hub-market/Magento_Catalog/templates/product/view/summary.phtml';
$escaper = $om->get(\Magento\Framework\Escaper::class);
$db = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$mediaBase = $storeManager->getStore(1)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
$failures = 0;

$check = function (string $label, bool $ok, string $detail = '') use (&$failures) {
    printf("%s %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $detail !== '' && !$ok ? "\n     $detail" : '');
    $failures += $ok ? 0 : 1;
};

/* Renders the template for a product; a PHP warning or notice fails the render. */
$render = function ($product) use ($template, $escaper): string {
    $block = new class ($product) {
        private $product;

        public function __construct($product)
        {
            $this->product = $product;
        }

        public function getProduct()
        {
            return $this->product;
        }
    };
    set_error_handler(static function (int $no, string $msg, string $file, int $line) {
        throw new \ErrorException($msg, 0, $no, $file, $line);
    });
    ob_start();
    try {
        (static function () use ($block, $escaper, $template) {
            include $template;
        })();
    } finally {
        $html = (string) ob_get_clean();
        restore_error_handler();
    }
    return trim($html);
};

$fake = function (string $shortDescription, int $storeId = 1) use ($om) {
    return $om->create(\Magento\Catalog\Model\Product::class)
        ->setStoreId($storeId)
        ->setShortDescription($shortDescription)
        ->setDescription('');
};

$srcs = function (string $html, string $tag = 'img|video|source|audio|iframe'): array {
    preg_match_all('#<(?:' . $tag . ')\b[^>]*\ssrc="([^"]*)"#i', $html, $m);
    return array_map(static fn($s) => html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $m[1]);
};

$fileOf = function (string $url) use ($mediaBase): ?string {
    return str_starts_with($url, $mediaBase) ? BP . '/pub/media/' . rawurldecode(substr($url, strlen($mediaBase))) : null;
};

// 1. QA's product.
$repo = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
foreach ([1 => 'ar', 3 => 'en'] as $storeId => $code) {
    $html = $render($repo->getById(2546, false, $storeId, true));
    $images = $srcs($html, 'img');
    $check("2546 /$code: both text lines are shown",
        substr_count($html, '<span dir="auto">') === 2 && str_contains($html, 'الماتريال : ستان') && str_contains($html, 'العناية : قابل للكي'),
        $html);
    $check("2546 /$code: the picture is shown, at the store's media URL",
        count($images) === 1 && str_starts_with($images[0], $mediaBase) && !str_contains($html, '{{'), $html);
    $file = $images ? $fileOf($images[0]) : null;
    $check("2546 /$code: that file exists under pub/media", $file !== null && is_file($file), (string) $file);
}

// 2. Made-up inputs.
$cases = [
    'escaped markup is decoded, the picture kept' => [
        '&lt;p&gt;Line one&lt;br&gt;&lt;img src=&quot;{{media url=&amp;quot;wysiwyg/a.png&amp;quot;}}&quot;&gt;&lt;/p&gt;',
        fn($h, $s) => $s === [$mediaBase . 'wysiwyg/a.png'] && str_contains($h, 'Line one'),
    ],
    'unquoted media directive' => [
        '<img src="{{media url=wysiwyg/b.png}}" alt="B">',
        fn($h, $s) => $s === [$mediaBase . 'wysiwyg/b.png'] && str_contains($h, 'alt="B"'),
    ],
    'video with a <source> keeps its file and type' => [
        "Intro\n<video controls width=\"640\" height=\"360\" poster=\"/media/p.jpg\"><source src=\"{{media url=&quot;wysiwyg/v.mp4&quot;}}\" type=\"video/mp4\"></video>Outro",
        fn($h, $s) => $s === [$mediaBase . 'wysiwyg/v.mp4'] && str_contains($h, 'type="video/mp4"') && str_contains($h, 'width="640"')
            && str_contains($h, 'poster=') && preg_match('#Intro.*<video.*</video>.*Outro#s', $h),
    ],
    'video given as a src attribute' => [
        '<video src="https://cdn.example.com/v.webm"></video>',
        fn($h, $s) => $s === ['https://cdn.example.com/v.webm'],
    ],
    'audio' => [
        '<audio controls><source src="/media/a.mp3" type="audio/mpeg"></audio>',
        fn($h, $s) => $s === ['/media/a.mp3'] && str_contains($h, '<audio controls'),
    ],
    'YouTube embed' => [
        '<p><iframe width="560" height="314" src="https://www.youtube.com/embed/abc123" allowfullscreen="allowfullscreen"></iframe></p>',
        fn($h, $s) => $s === ['https://www.youtube.com/embed/abc123'],
    ],
    'Vimeo embed' => [
        '<iframe src="//player.vimeo.com/video/42"></iframe>',
        fn($h, $s) => $s === ['https://player.vimeo.com/video/42'],
    ],
    'an iframe from any other site is dropped' => [
        'Text<iframe src="https://evil.example.com/x"></iframe>',
        fn($h, $s) => $s === [] && str_contains($h, 'Text') && !str_contains($h, 'evil'),
    ],
    'javascript: src is dropped' => [
        '<img src="javascript:alert(1)"><video src="javascript:alert(1)"></video>Text',
        fn($h, $s) => $s === [] && !str_contains(strtolower($h), 'javascript'),
    ],
    'event handlers and other attributes are not carried over' => [
        '<img src="/media/x.png" onerror="alert(1)" class="evil" style="position:fixed">',
        fn($h, $s) => $s === ['/media/x.png'] && !str_contains($h, 'onerror') && !str_contains($h, 'evil') && !str_contains($h, 'fixed'),
    ],
    'data-src alone is not a src' => [
        '<img data-src="/media/lazy.png">Text',
        fn($h, $s) => $s === [] && str_contains($h, 'Text'),
    ],
    'an inline data: picture is allowed on <img> only' => [
        '<img src="data:image/png;base64,iVBORw0KGgo="><video src="data:video/mp4;base64,AAAA"></video>',
        fn($h, $s) => count($s) === 1 && str_starts_with($s[0], 'data:image/png'),
    ],
    'a widget directive is still dropped' => [
        'Text {{widget type="Magento\\Cms\\Block\\Widget\\Block" block_id="1"}}',
        fn($h, $s) => $s === [] && str_contains($h, 'Text') && !str_contains($h, 'widget'),
    ],
    'a forged placeholder renders nothing' => [
        "Text&#26;0&#26;\n\x1A0\x1A",
        fn($h, $s) => $s === [] && !str_contains($h, 'hm-pdp__summary-media'),
    ],
    'text-only markup is unchanged: <br> between lines, no media span' => [
        '<p>One<br>Two</p><ul><li>Three</li></ul>',
        fn($h, $s) => str_contains($h, '<span dir="auto">One</span><br><span dir="auto">Two</span><br><span dir="auto">Three</span>')
            && !str_contains($h, 'hm-pdp__summary-media'),
    ],
];
foreach ($cases as $label => [$input, $test]) {
    $html = $render($fake($input));
    $check($label, (bool) $test($html, $srcs($html)), $html);
}

// 3. Every stored value.
$rows = $db->fetchAll(
    "SELECT t.entity_id, t.store_id, t.value FROM catalog_product_entity_text t
       JOIN eav_attribute a ON a.attribute_id = t.attribute_id AND a.attribute_code = 'short_description'
       JOIN eav_entity_type et ON et.entity_type_id = a.entity_type_id AND et.entity_type_code = 'catalog_product'
      WHERE TRIM(COALESCE(t.value, '')) <> ''"
);
$broken = [];
$lost = [];
foreach ($rows as $row) {
    $storeId = (int) $row['store_id'] ?: 1;
    try {
        $html = $render($fake((string) $row['value'], $storeId));
    } catch (\Throwable $e) {
        $broken[] = "{$row['entity_id']}@{$row['store_id']}: " . $e->getMessage();
        continue;
    }
    $decoded = html_entity_decode((string) $row['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $wanted = preg_match_all('#<(?:img|source|iframe)\b[^>]*\ssrc=["\']?(?:https?:|/|\{\{media)#i', $decoded)
        + preg_match_all('#<(?:video|audio)\b[^>]*\ssrc=["\']?(?:https?:|/|\{\{media)#i', $decoded);
    if (count($srcs($html)) < $wanted) {
        $lost[] = "{$row['entity_id']}@{$row['store_id']}";
    }
}
$check(sprintf('all %d stored short descriptions render without a PHP warning', count($rows)), !$broken, implode("\n     ", $broken));
$check('every stored picture/video with a usable src reaches the page', !$lost, implode(', ', $lost));

echo $failures ? "\n$failures check(s) FAILED\n" : "\nAll checks passed\n";
exit($failures ? 1 : 0);
