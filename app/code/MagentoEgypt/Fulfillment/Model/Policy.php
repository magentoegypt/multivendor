<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

/** Strict versioned configuration. All monetary values are base-currency minor units. */
final class Policy
{
    public function parse(string $json): array
    {
        $p = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($p) || ($p['version'] ?? null) !== 1) {
            throw new \InvalidArgumentException('A version 1 fulfillment policy is required.');
        }
        if (!preg_match('/^[A-Z]{3}$/D', $p['currency'] ?? '') || ($p['minor_digits'] ?? null) !== 2) {
            throw new \InvalidArgumentException('Specify an ISO currency and minor_digits: 2.');
        }
        foreach (['sources', 'vendors', 'products', 'rates'] as $key) {
            if (!isset($p[$key]) || !is_array($p[$key]) || !array_is_list($p[$key]) || count($p[$key]) > 5000) {
                throw new \InvalidArgumentException("Invalid $key list.");
            }
        }
        $sources = $vendors = $products = $rates = [];
        foreach ($p['sources'] as $s) {
            $this->identifier($s['code'] ?? null);
            $this->integer($s['vendor_id'] ?? null, 0, 2147483647);
            $this->integer($s['priority'] ?? null, 0, 100000);
            $this->destination($s['location'] ?? []);
            if (!$s['location']['city_id'] || !in_array($s['kind'] ?? '', ['vendor', 'hub'], true)
                || ($s['kind'] === 'hub' && $s['vendor_id'] !== 0) || isset($sources[$s['code']])) {
                throw new \InvalidArgumentException('Invalid or duplicate source ownership.');
            }
            $sources[$s['code']] = $s;
        }
        foreach ($p['vendors'] as $v) {
            $this->integer($v['vendor_id'] ?? null, 0, 2147483647);
            $this->modes($v['modes'] ?? null);
            if (isset($vendors[$v['vendor_id']])) throw new \InvalidArgumentException('Duplicate vendor policy.');
            $vendors[$v['vendor_id']] = $v['modes'];
        }
        foreach ($p['products'] as $v) {
            $this->identifier($v['sku'] ?? null);
            $this->integer($v['vendor_id'] ?? null, 0, 2147483647);
            $this->modes($v['modes'] ?? null);
            if (!isset($vendors[$v['vendor_id']]) || isset($products[$v['sku']])) {
                throw new \InvalidArgumentException('Product policy requires one configured owner.');
            }
            if (isset($v['coverage'])) {
                if (!is_array($v['coverage']) || !array_is_list($v['coverage']) || count($v['coverage']) > 500) {
                    throw new \InvalidArgumentException('Invalid product coverage list.');
                }
                foreach ($v['coverage'] as $destination) $this->destination($destination);
            }
            $products[$v['sku']] = $v;
        }
        foreach ($p['rates'] as $r) {
            $this->identifier($r['id'] ?? null);
            if (isset($rates[$r['id']]) || !isset($sources[$r['source'] ?? ''])
                || !in_array($r['leg'] ?? '', ['direct', 'inbound', 'outbound'], true)) {
                throw new \InvalidArgumentException('Invalid rate identity, source or leg.');
            }
            $this->destination($r['destination'] ?? []);
            if (!in_array($r['mode'] ?? '', ['vendor', 'marketplace', 'hub'], true)
                || ($r['leg'] === 'direct' && $r['mode'] === 'hub')
                || ($r['leg'] !== 'direct' && $r['mode'] !== 'hub')
                || ($sources[$r['source']]['kind'] === 'hub' && $r['mode'] === 'vendor')) {
                throw new \InvalidArgumentException('Rate fulfillment mode is incompatible with its leg or source.');
            }
            foreach (['base_minor', 'per_unit_minor', 'per_kg_minor', 'cost_base_minor', 'cost_per_unit_minor', 'cost_per_kg_minor'] as $key) {
                $this->integer($r[$key] ?? null, 0, 100000000);
            }
            foreach (['revenue_owner', 'cost_owner'] as $key) {
                if (!in_array($r[$key] ?? '', ['vendor', 'marketplace'], true)) {
                    throw new \InvalidArgumentException('Each rate needs revenue and cost owners.');
                }
            }
            $s = $sources[$r['source']];
            if ($r['leg'] === 'inbound') {
                $hub = $sources[$r['hub'] ?? ''] ?? null;
                if (!$hub || $hub['kind'] !== 'hub' || $s['kind'] !== 'vendor'
                    || $r['destination'] != $hub['location']) {
                    throw new \InvalidArgumentException('Inbound rates must point to an exact configured hub location.');
                }
            } elseif (isset($r['hub'])) {
                throw new \InvalidArgumentException('Only an inbound rate can specify a destination hub.');
            } elseif ($r['leg'] === 'outbound' && ($s['kind'] !== 'hub'
                || $r['revenue_owner'] !== 'marketplace' || $r['cost_owner'] !== 'marketplace')) {
                throw new \InvalidArgumentException('Consolidated outbound fees and costs belong to the marketplace.');
            }
            // Equal-specificity matches must not depend on administrator row ordering.
            foreach ($rates as $existing) {
                if ($existing['source'] === $r['source'] && $existing['leg'] === $r['leg']
                    && $existing['mode'] === $r['mode']
                    && ($existing['hub'] ?? '') === ($r['hub'] ?? '')
                    && $existing['destination'] == $r['destination']) {
                    throw new \InvalidArgumentException('Duplicate rate destination.');
                }
            }
            $rates[$r['id']] = $r;
        }
        return ['version'=>1, 'currency'=>$p['currency'], 'minor_digits'=>2,
            'sources'=>$sources, 'vendors'=>$vendors, 'products'=>$products, 'rates'=>$rates];
    }

    public function destination(array $d): void
    {
        if (!in_array($d['country'] ?? '', ['EG', 'SA', 'AE', 'US'], true)) {
            throw new \InvalidArgumentException('Unsupported destination country.');
        }
        $this->integer($d['city_id'] ?? null, 0, 2147483647);
        $this->integer($d['locality_id'] ?? null, 0, 2147483647);
        if ($d['locality_id'] && !$d['city_id']) throw new \InvalidArgumentException('Locality requires a city.');
    }

    public function integer(mixed $value, int $min, int $max): void
    {
        if (!is_int($value) || $value < $min || $value > $max) throw new \InvalidArgumentException('Invalid integer value.');
    }

    private function identifier(mixed $value): void
    {
        if (!is_string($value) || $value === '' || strlen($value) > 64 || preg_match('/[\x00-\x1f]/', $value)) {
            throw new \InvalidArgumentException('Invalid identifier.');
        }
    }

    private function modes(mixed $modes): void
    {
        if (!is_array($modes) || !array_is_list($modes) || count(array_unique($modes)) !== count($modes)
            || array_diff($modes, ['vendor', 'marketplace', 'hub'])) {
            throw new \InvalidArgumentException('Modes must contain vendor, marketplace or hub. Empty means no service.');
        }
    }
}
