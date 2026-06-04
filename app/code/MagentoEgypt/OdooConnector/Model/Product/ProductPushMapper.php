<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Maps a Magento product to Odoo product.template values for outbound writes,
 * and computes the checksum of the Odoo representation we wrote so a later pull
 * recognizes it as an echo (architecture doc section 2 / section 6).
 */
class ProductPushMapper
{
    private ProductMapper $productMapper;
    private Filesystem $filesystem;
    private CategoryResolver $categoryResolver;

    public function __construct(ProductMapper $productMapper, Filesystem $filesystem, CategoryResolver $categoryResolver)
    {
        $this->productMapper = $productMapper;
        $this->filesystem = $filesystem;
        $this->categoryResolver = $categoryResolver;
    }

    /**
     * @return array<string, mixed>
     */
    public function toOdooValues(ProductInterface $product): array
    {
        $values = [
            'name' => (string)$product->getName(),
            'default_code' => (string)$product->getSku(),
            'list_price' => (float)$product->getPrice(),
            // Odoo 18+ dropped the 'product' (storable) type: a storable good is now
            // type='consu' (Goods) + is_storable=true. Sending type='product' is rejected
            // by Odoo 19; is_storable keeps the product inventory-tracked so the MSI
            // on-hand sync (stock.quant) still applies.
            'type' => 'consu',
            'is_storable' => true,
            // Product Status: Magento 1=enabled / 2=disabled -> Odoo sellable flag.
            // (sale_ok rather than active, so disabling never archives/hides the
            // record from our SKU re-attach search and never breaks idempotency.)
            'sale_ok' => (int)$product->getStatus() === 1,
            // Product Visibility (1=Not Visible,2=Catalog,3=Search,4=Catalog+Search) — Magento-only.
            'x_magento_visibility' => (int)$product->getVisibility(),
        ];

        $description = $this->attrValue($product, 'description');
        if ($description !== null) {
            $values['description_sale'] = $description;
        }

        $special = $this->attrValue($product, 'special_price');
        if ($special !== null && is_numeric($special)) {
            $values['x_magento_special_price'] = (float)$special;
        }

        // Cost -> Odoo standard_price (margin reporting). Sourced from the Magento
        // 'cost' attribute; kept out of x_magento_attributes via the skip list.
        $cost = $this->attrValue($product, 'cost');
        if ($cost !== null && is_numeric($cost)) {
            $values['standard_price'] = (float)$cost;
        }

        // Weight -> Odoo weight (delivery/shipping). Only when set, so we never
        // overwrite an existing Odoo weight with 0.
        $weight = $product->getWeight();
        if ($weight !== null && (float)$weight > 0.0) {
            $values['weight'] = (float)$weight;
        }

        // Barcode/EAN -> Odoo barcode. Magento has no native barcode field, so we
        // read a 'barcode' attribute when present. NOTE: Odoo enforces barcode
        // uniqueness; duplicate values are rejected by Odoo on write.
        $barcode = $this->attrValue($product, 'barcode');
        if ($barcode !== null) {
            $values['barcode'] = $barcode;
        }

        // Short description -> Odoo custom field (no native Odoo equivalent).
        $shortDescription = $this->attrValue($product, 'short_description');
        if ($shortDescription !== null) {
            $values['x_magento_short_description'] = $shortDescription;
        }

        // SEO meta -> Odoo website fields (requires the website module; conditional on attrs).
        $metaTitle = $this->attrValue($product, 'meta_title');
        if ($metaTitle !== null) {
            $values['website_meta_title'] = $metaTitle;
        }
        $metaDescription = $this->attrValue($product, 'meta_description');
        if ($metaDescription !== null) {
            $values['website_meta_description'] = $metaDescription;
        }
        $metaKeyword = $this->attrValue($product, 'meta_keyword');
        if ($metaKeyword !== null) {
            $values['website_meta_keywords'] = $metaKeyword;
        }

        $categId = $this->categoryResolver->resolvePrimaryCategId((array)$product->getCategoryIds());
        if ($categId !== null) {
            $values['categ_id'] = $categId;
        }

        $attributes = $this->collectAttributes($product);
        if ($attributes !== []) {
            $values['x_magento_attributes'] = json_encode($attributes);
        }

        $image = $this->imageBase64($product);
        if ($image !== null) {
            // Odoo stores the main product image as base64 in image_1920 and derives
            // the resized variants itself. Push-only enrichment — intentionally kept out
            // of the echo checksum so name/price still drive echo detection on pull.
            $values['image_1920'] = $image;
        }

        // Extra gallery images -> product_template_image_ids. Replace-then-add (5,0,0)
        // so re-pushes don't accumulate duplicates.
        $gallery = $this->galleryImages($product);
        if ($gallery !== []) {
            $commands = [[5, 0, 0]];
            foreach ($gallery as $img) {
                $commands[] = [0, 0, $img];
            }
            $values['product_template_image_ids'] = $commands;
        }

        return $values;
    }

    private function attrValue(ProductInterface $product, string $code): ?string
    {
        $attr = $product->getCustomAttribute($code);
        if ($attr === null) {
            return null;
        }
        $val = $attr->getValue();

        return ($val === null || $val === '') ? null : (string)$val;
    }

    /**
     * Curated user-defined product attributes (color, material, brand, …), excluding
     * fields handled explicitly elsewhere. Stored as JSON in Odoo x_magento_attributes.
     *
     * @return array<string, scalar>
     */
    private function collectAttributes(ProductInterface $product): array
    {
        static $skip = [
            'description', 'short_description', 'image', 'small_image', 'thumbnail', 'media_gallery',
            'special_price', 'special_from_date', 'special_to_date', 'status', 'visibility', 'category_ids',
            'price', 'cost', 'weight', 'barcode', 'name', 'sku', 'url_key', 'url_path', 'meta_title', 'meta_description', 'meta_keyword',
            'tier_price', 'quantity_and_stock_status', 'options_container', 'gift_message_available',
            'custom_design', 'page_layout', 'swatch_image', 'required_options', 'has_options', 'image_label',
        ];
        $out = [];
        foreach ($product->getCustomAttributes() as $attribute) {
            $code = $attribute->getAttributeCode();
            if (in_array($code, $skip, true)) {
                continue;
            }
            $value = $attribute->getValue();
            if (is_scalar($value) && (string)$value !== '') {
                $out[$code] = $value;
            }
        }

        return $out;
    }

    /**
     * Base64 of the product's main image file, or null when there is none.
     */
    private function imageBase64(ProductInterface $product): ?string
    {
        $image = (string)$product->getImage();
        if ($image === '' || $image === 'no_selection') {
            return null;
        }

        try {
            $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $path = 'catalog/product' . $image;
            if (!$media->isExist($path)) {
                return null;
            }
            $contents = $media->readFile($path);

            return $contents !== '' ? base64_encode($contents) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Base64 of the product's extra gallery images (excluding the main image), capped to
     * keep the payload bounded. Each item is shaped for product_template_image_ids.
     *
     * @return array<int, array<string, string>>
     */
    private function galleryImages(ProductInterface $product, int $limit = 5): array
    {
        $entries = $product->getMediaGalleryEntries();
        if (!is_array($entries) || $entries === []) {
            return [];
        }
        $mainFile = (string)$product->getImage();
        $out = [];
        try {
            $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            foreach ($entries as $entry) {
                if (count($out) >= $limit) {
                    break;
                }
                $file = (string)$entry->getFile();
                if ($file === '' || $file === $mainFile || (string)$entry->getMediaType() !== 'image') {
                    continue;
                }
                $path = 'catalog/product' . $file;
                if (!$media->isExist($path)) {
                    continue;
                }
                $contents = $media->readFile($path);
                if ($contents === '') {
                    continue;
                }
                $out[] = ['name' => basename($file), 'image_1920' => base64_encode($contents)];
            }
        } catch (\Throwable $e) {
            return $out;
        }

        return $out;
    }

    /**
     * Checksum of the Odoo-shaped record equivalent to the values just written —
     * matches what ProductMapper::checksum() computes when the pull re-reads it.
     *
     * @param array<string, mixed> $odooValues
     */
    public function expectedOdooChecksum(array $odooValues): string
    {
        return $this->productMapper->checksum([
            'default_code' => $odooValues['default_code'] ?? false,
            'name' => $odooValues['name'] ?? false,
            'list_price' => $odooValues['list_price'] ?? 0,
            'barcode' => $odooValues['barcode'] ?? false,
            'type' => $odooValues['type'] ?? false,
        ]);
    }
}
