<?php
/**
 * Hub Market — build the catalogue image placeholder.
 *
 * This install had NO placeholder configured at all: `catalog/placeholder/*`
 * held no rows and pub/media/catalog/product/placeholder/ did not exist, so
 * every product without an image fell back to the placeholder Magento ships
 * with its own module — a grey MAGENTO LOGO. On the storefront that reads as a
 * broken page rather than as "no photo yet", which is how it was reported
 * (two cards on the search results page for "test").
 *
 * Seven enabled, visible products currently have no image, and the two on that
 * search page have none anywhere: not on the parent, not on any of the
 * configurable's sixteen children, and no source file on disk. So there is
 * nothing to re-link — this is NOT the orphaned-media case that has bitten this
 * catalogue before. The correct fix is a placeholder that looks deliberate.
 *
 * GLYPH ONLY, NO TEXT — on purpose. A worded placeholder ("No image") would be
 * baked into the pixels and therefore English on the Arabic store, which is the
 * exact class of bug this project keeps finding. A picture glyph needs no
 * translation.
 *
 * Palette is the theme's own (web/css/source/_hm-tokens.less):
 *   surface  #f5f7fa  (@hm-neutral-100)
 *   line     #cbd3e2  (@hm-neutral-300)
 *
 * Writes the file AND points the four placeholder config paths at it, because
 * `bin/magento config:set` cannot: that group uses `clone_fields` with a
 * `clone_model`, so `image_placeholder` and friends are generated at runtime
 * from the media attribute list and no such path exists in system.xml for the
 * CLI to validate against. Going through the config Writer is the supported way.
 *
 * The stored value is the BARE FILENAME. Magento\Catalog\Model\View\Asset\
 * Placeholder::getRelativePath() builds the URL as
 * media + catalog/product + getModule() ("placeholder") + the config value, so
 * storing "placeholder/hm-placeholder.png" would resolve to .../placeholder/
 * placeholder/hm-placeholder.png and 404 back to the Magento logo.
 *
 * Usage:  php8.4 dev/tools/hub-market/build-image-placeholder.php
 */
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../../app/bootstrap.php';

const SIZE = 800;

$dir = dirname(__DIR__, 3) . '/pub/media/catalog/product/placeholder';
$out = $dir . '/hm-placeholder.png';

if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create {$dir}\n");
    exit(1);
}

$im = imagecreatetruecolor(SIZE, SIZE);
imagealphablending($im, true);
imageantialias($im, true);

$surface = imagecolorallocate($im, 0xf5, 0xf7, 0xfa);
$line    = imagecolorallocate($im, 0xcb, 0xd3, 0xe2);
imagefilledrectangle($im, 0, 0, SIZE - 1, SIZE - 1, $surface);

/** Filled rounded rectangle. */
$rounded = static function ($img, int $x1, int $y1, int $x2, int $y2, int $r, int $colour): void {
    $d = $r * 2;
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $colour);
    imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $colour);
    imagefilledellipse($img, $x1 + $r, $y1 + $r, $d, $d, $colour);
    imagefilledellipse($img, $x2 - $r, $y1 + $r, $d, $d, $colour);
    imagefilledellipse($img, $x1 + $r, $y2 - $r, $d, $d, $colour);
    imagefilledellipse($img, $x2 - $r, $y2 - $r, $d, $d, $colour);
};

//  Photo frame, drawn as an outline: solid shape, then the inside knocked back
//  out in the surface colour.
$fx1 = 210; $fy1 = 250; $fx2 = 590; $fy2 = 550;
$stroke = 9; $radius = 26;
$rounded($im, $fx1, $fy1, $fx2, $fy2, $radius, $line);
$rounded($im, $fx1 + $stroke, $fy1 + $stroke, $fx2 - $stroke, $fy2 - $stroke, $radius - 4, $surface);

//  Sun and hills. Every point below sits inside the frame's inner box
//  (x 219..581, y 259..541), so the glyph needs no clipping.
imagefilledellipse($im, 300, 332, 56, 56, $line);
foreach ([
    [245, 534, 372, 400, 499, 534],
    [386, 534, 472, 438, 558, 534],
] as $pts) {
    imagefilledpolygon($im, $pts, $line);
}

imagepng($im, $out, 9);
imagedestroy($im);
@chmod($out, 0664);

printf("wrote %s (%d bytes, %dx%d)\n", $out, filesize($out), SIZE, SIZE);

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

/** @var \Magento\Framework\App\Config\Storage\WriterInterface $writer */
$writer = $om->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

foreach (['image', 'small_image', 'thumbnail', 'swatch_image'] as $type) {
    $writer->save("catalog/placeholder/{$type}_placeholder", basename($out));
    printf("config catalog/placeholder/%s_placeholder = %s\n", $type, basename($out));
}

echo "\nDone. Now run: php8.4 bin/magento cache:flush\n";
