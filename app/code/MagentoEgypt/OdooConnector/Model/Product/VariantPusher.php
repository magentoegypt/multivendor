<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;

/**
 * Maps a Magento CONFIGURABLE product to an Odoo product.template with variants:
 *  - its super-attributes -> product.attribute + product.attribute.value (+ attribute_line_ids),
 *  - each child simple product -> the matching Odoo variant (product.product), by attribute combo,
 *  - the now-redundant standalone child template(s) are archived.
 *
 * Idempotent: re-running re-maps children (default_code is re-asserted) without
 * duplicating attribute lines. A converted template's default_code is cleared by Odoo
 * (multi-variant), so re-attach uses the entity map (SKU -> template id) before falling
 * back to a default_code search / create.
 *
 * NOTE: per-variant selling price is not set here (Odoo derives it from the template +
 * price_extra); only the variant default_code (SKU) is set, which is what order/inventory
 * resolution needs. Attribute-set CHANGES on an already-converted product are not
 * re-synced (lines are left as-is); a re-conversion would be needed for that.
 */
class VariantPusher
{
    private const ENTITY_TYPE = 'product';
    private const ODOO_MODEL = 'product.template';

    private OdooClient $odooClient;
    private ProductPushMapper $pushMapper;
    private MapManager $mapManager;
    private ConfigurableResource $configurableResource;

    /** @var array<string, int> attribute name -> id */
    private array $attrCache = [];
    /** @var array<string, int> "attrId|value" -> id */
    private array $valCache = [];

    public function __construct(
        OdooClient $odooClient,
        ProductPushMapper $pushMapper,
        MapManager $mapManager,
        ConfigurableResource $configurableResource
    ) {
        $this->odooClient = $odooClient;
        $this->pushMapper = $pushMapper;
        $this->mapManager = $mapManager;
        $this->configurableResource = $configurableResource;
    }

    /**
     * True when a simple product is a child of one or more configurable products — such
     * products are synced as variants of their parent, never as a standalone template.
     */
    public function isConfigurableChild(ProductInterface $product): bool
    {
        try {
            return $this->configurableResource->getParentIdsByChild((int)$product->getId()) !== [];
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{action: string, odoo_id: int, sku: string, variants?: int, reason?: string}
     */
    public function pushConfigurable(ProductInterface $product, string $correlationId): array
    {
        $sku = (string)$product->getSku();
        $type = $product->getTypeInstance();

        $labels = [];
        foreach ($type->getConfigurableAttributes($product) as $sa) {
            $attr = $sa->getProductAttribute();
            if ($attr !== null) {
                $labels[$attr->getAttributeCode()] = (string)($attr->getStoreLabel() ?: $attr->getAttributeCode());
            }
        }
        if ($labels === []) {
            return ['action' => 'skipped', 'odoo_id' => 0, 'sku' => $sku, 'reason' => 'configurable without super-attributes'];
        }

        $valuesByCode = [];
        $childCombos = [];
        foreach ($type->getUsedProducts($product) as $child) {
            $combo = [];
            foreach ($labels as $code => $label) {
                $val = (string)($child->getAttributeText($code) ?: $child->getData($code));
                $combo[] = $val;
                if ($val !== '') {
                    $valuesByCode[$code][$val] = true;
                }
            }
            sort($combo);
            $childCombos[(string)$child->getSku()] = implode('|', $combo);
        }
        if ($valuesByCode === []) {
            return ['action' => 'skipped', 'odoo_id' => 0, 'sku' => $sku, 'reason' => 'configurable without child attribute values'];
        }

        $templateId = $this->resolveTemplateId($product, $sku, array_key_first($childCombos) ?: null);

        // Add attribute lines only if the template has none yet (avoid duplicating on re-push).
        $read = $this->odooClient->executeKw(self::ODOO_MODEL, 'read', [[$templateId]], ['fields' => ['attribute_line_ids']]);
        $hasLines = is_array($read) && !empty($read[0]['attribute_line_ids']);
        if (!$hasLines) {
            $lines = [];
            foreach ($labels as $code => $label) {
                $attrId = $this->attrId($label);
                $valueIds = [];
                foreach (array_keys($valuesByCode[$code]) as $val) {
                    $valueIds[] = $this->valId($attrId, (string)$val);
                }
                $lines[] = [0, 0, ['attribute_id' => $attrId, 'value_ids' => [[6, 0, $valueIds]]]];
            }
            $this->odooClient->executeKw(self::ODOO_MODEL, 'write', [[$templateId], ['attribute_line_ids' => $lines]]);
        }

        $mapped = $this->mapChildrenToVariants($templateId, $childCombos);

        $this->mapManager->link([
            'entity_type' => self::ENTITY_TYPE,
            'magento_natural_key' => $sku,
            'magento_id' => (string)$product->getId(),
            'odoo_model' => self::ODOO_MODEL,
            'odoo_id' => $templateId,
            'last_direction' => EntityMap::DIRECTION_M2O,
            'sync_status' => EntityMap::STATUS_LINKED,
            'website_id' => 0,
            'last_correlation_id' => $correlationId,
        ]);

        return ['action' => $hasLines ? 'update' : 'create', 'odoo_id' => $templateId, 'sku' => $sku, 'variants' => $mapped];
    }

    /**
     * Existing variant template via the map (a converted template's default_code is
     * cleared, so the map is authoritative); else by default_code (first push, pre-
     * conversion); else create a fresh template from the base push values.
     */
    private function resolveTemplateId(ProductInterface $product, string $sku, ?string $childSkuHint): int
    {
        $map = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $sku, 0);
        if ($map !== null && $map->getData('odoo_id')) {
            $id = (int)$map->getData('odoo_id');
            $chk = $this->odooClient->executeKw(self::ODOO_MODEL, 'search', [[['id', '=', $id]]], ['limit' => 1]);
            if (is_array($chk) && isset($chk[0])) {
                return $id;
            }
        }
        $found = $this->odooClient->executeKw(self::ODOO_MODEL, 'search', [[['default_code', '=', $sku]]], ['limit' => 1]);
        if (is_array($found) && isset($found[0])) {
            return (int)$found[0];
        }
        // Already converted but unmapped: a converted template's default_code is cleared,
        // so recover it from one of its variants (a child SKU) — avoids creating a duplicate.
        if ($childSkuHint !== null && $childSkuHint !== '') {
            $variant = $this->odooClient->executeKw('product.product', 'search_read', [[['default_code', '=', $childSkuHint]]], ['fields' => ['product_tmpl_id'], 'limit' => 1]);
            if (is_array($variant) && isset($variant[0]['product_tmpl_id'][0])) {
                return (int)$variant[0]['product_tmpl_id'][0];
            }
        }

        return (int)$this->odooClient->executeKw(self::ODOO_MODEL, 'create', [$this->pushMapper->toOdooValues($product)]);
    }

    /**
     * Assign each child SKU to its matching generated variant (by attribute-value combo),
     * and archive any standalone template that still carries that SKU. Returns matched count.
     *
     * @param array<string, string> $childCombos child SKU -> sorted "val|val" combo key
     */
    private function mapChildrenToVariants(int $templateId, array $childCombos): int
    {
        $vars = $this->odooClient->executeKw(
            'product.product',
            'search_read',
            [[['product_tmpl_id', '=', $templateId]]],
            ['fields' => ['id', 'product_template_attribute_value_ids']]
        );
        $ptavIds = [];
        foreach ($vars as $v) {
            foreach ($v['product_template_attribute_value_ids'] as $p) {
                $ptavIds[$p] = true;
            }
        }
        $ptavName = [];
        if ($ptavIds !== []) {
            foreach ($this->odooClient->executeKw('product.template.attribute.value', 'read', [array_keys($ptavIds)], ['fields' => ['id', 'name']]) as $r) {
                $ptavName[$r['id']] = $r['name'];
            }
        }
        $variantByCombo = [];
        foreach ($vars as $v) {
            $names = array_map(static fn ($p) => $ptavName[$p] ?? '', $v['product_template_attribute_value_ids']);
            sort($names);
            $variantByCombo[implode('|', $names)] = (int)$v['id'];
        }

        $mapped = 0;
        foreach ($childCombos as $childSku => $key) {
            $variantId = $variantByCombo[$key] ?? null;
            if ($variantId === null) {
                continue; // child outside the generated grid — left as-is, never breaks
            }
            $this->odooClient->executeKw('product.product', 'write', [[$variantId], ['default_code' => $childSku]]);
            $dups = $this->odooClient->executeKw(
                'product.product',
                'search_read',
                [[['default_code', '=', $childSku]]],
                ['fields' => ['id', 'product_tmpl_id'], 'context' => ['active_test' => false]]
            );
            foreach ($dups as $d) {
                $dTmpl = (int)$d['product_tmpl_id'][0];
                if ($dTmpl === $templateId || (int)$d['id'] === $variantId) {
                    continue;
                }
                try {
                    $this->odooClient->executeKw(self::ODOO_MODEL, 'write', [[$dTmpl], ['active' => false]]);
                } catch (\Throwable $e) {
                    // non-fatal: a standalone child template that can't be archived just lingers
                }
            }
            $mapped++;
        }

        return $mapped;
    }

    private function attrId(string $name): int
    {
        if (isset($this->attrCache[$name])) {
            return $this->attrCache[$name];
        }
        $found = $this->odooClient->executeKw('product.attribute', 'search', [[['name', '=', $name]]], ['limit' => 1]);
        $id = (is_array($found) && isset($found[0]))
            ? (int)$found[0]
            : (int)$this->odooClient->executeKw('product.attribute', 'create', [['name' => $name, 'create_variant' => 'always']]);

        return $this->attrCache[$name] = $id;
    }

    private function valId(int $attrId, string $value): int
    {
        $key = $attrId . '|' . $value;
        if (isset($this->valCache[$key])) {
            return $this->valCache[$key];
        }
        $found = $this->odooClient->executeKw('product.attribute.value', 'search', [[['name', '=', $value], ['attribute_id', '=', $attrId]]], ['limit' => 1]);
        $id = (is_array($found) && isset($found[0]))
            ? (int)$found[0]
            : (int)$this->odooClient->executeKw('product.attribute.value', 'create', [['name' => $value, 'attribute_id' => $attrId]]);

        return $this->valCache[$key] = $id;
    }
}
