<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

/** Read-only, deterministic allocation proposal; never reserves inventory or posts money. */
final class Planner
{
    public function plan(array $policy, array $destination, array $lines, array $inventory, string $strategy = 'direct'): array
    {
        (new Policy())->destination($destination);
        if (!$destination['city_id'] || !in_array($strategy, ['direct', 'hub'], true) || !$lines || count($lines) > 100) {
            throw new \InvalidArgumentException('A city, 1–100 lines and direct/hub strategy are required.');
        }
        $base = ['version'=>1, 'status'=>'unavailable', 'currency'=>$policy['currency'],
            'minor_digits'=>2, 'strategy'=>$strategy, 'destination'=>$destination,
            'reservation'=>'not_reserved', 'groups'=>[], 'shipping_minor'=>null, 'issues'=>[]];
        $seen = [];
        foreach ($lines as $l) {
            if (!isset($l['sku'], $l['vendor_id'], $l['qty_milli'], $l['weight_grams']) || isset($seen[$l['sku']])) {
                throw new \InvalidArgumentException('Each SKU must have one aggregated line.');
            }
            (new Policy())->integer($l['vendor_id'], 0, 2147483647);
            (new Policy())->integer($l['qty_milli'], 1, 1000000);
            (new Policy())->integer($l['weight_grams'], 0, 1000000);
            $seen[$l['sku']] = true;
        }
        // Hub strategy evaluates one common destination hub for the entire basket.
        $hubs = $strategy === 'hub' ? array_keys(array_filter($policy['sources'], fn($s) => $s['kind'] === 'hub')) : [''];
        $plans = [];
        foreach ($hubs as $hub) {
            $plan = $this->allocate($policy, $destination, $lines, $inventory, $strategy, $hub, $base);
            if (!$plan['issues']) $plans[] = $plan;
            else $base['issues'] = $plan['issues'];
        }
        if (!$plans) {
            if (!$base['issues']) $base['issues'][] = ['code'=>'no_common_hub'];
            return $base;
        }
        usort($plans, fn($a, $b) => [$a['shipping_minor'], $a['hub']] <=> [$b['shipping_minor'], $b['hub']]);
        return $plans[0];
    }

    private function allocate(array $p, array $d, array $lines, array $inventory, string $strategy, string $hub, array $plan): array
    {
        $groups = [];
        usort($lines, fn($a, $b) => $a['sku'] <=> $b['sku']);
        foreach ($lines as $l) {
            $override = $p['products'][$l['sku']] ?? null;
            if ($override && $override['vendor_id'] !== $l['vendor_id']) {
                $plan['issues'][] = ['sku'=>$l['sku'], 'code'=>'owner_mismatch'];
                continue;
            }
            $modes = $override['modes'] ?? $p['vendors'][$l['vendor_id']] ?? [];
            $candidates = [];
            foreach ($p['sources'] as $code => $s) {
                if (($strategy === 'hub' && !in_array('hub', $modes, true)) || ($s['kind'] === 'vendor' && $s['vendor_id'] !== $l['vendor_id'])
                    || ($strategy === 'hub' && $s['kind'] === 'hub' && $code !== $hub)) continue;
                $stock = $inventory[$l['sku']][$code] ?? 0;
                if (!is_int($stock) || $stock <= 0) continue;
                $leg = $strategy === 'hub' ? ($code === $hub ? 'outbound' : 'inbound') : 'direct';
                $target = $leg === 'inbound' ? $p['sources'][$hub]['location'] : $d;
                $rate = $this->rate($p['rates'], $code, $leg, $target, $leg === 'inbound' ? $hub : '', $modes);
                if (!$rate) continue;
                // Even existing hub stock needs a configured last-mile route.
                if ($strategy === 'hub' && !$this->rate($p['rates'], $hub, 'outbound', $d)) continue;
                $candidates[] = ['source'=>$s, 'rate'=>$rate, 'stock'=>$stock, 'mode'=>$rate['mode']];
            }
            usort($candidates, fn($a, $b) => [$a['source']['priority'], $a['source']['code']] <=> [$b['source']['priority'], $b['source']['code']]);
            $remaining = $l['qty_milli'];
            foreach ($candidates as $c) {
                if (!$remaining) break;
                $qty = min($remaining, $c['stock']);
                $remaining -= $qty;
                $piece = ['sku'=>$l['sku'], 'vendor_id'=>$l['vendor_id'], 'qty_milli'=>$qty, 'weight_grams'=>$l['weight_grams']];
                $key = $c['source']['code'] . ':' . $l['vendor_id'] . ':' . $c['rate']['id'];
                if ($strategy === 'hub' && $c['source']['code'] === $hub) $key = 'hub:' . $hub;
                $this->append($groups, $key, $c['source']['code'], $c['rate'], $piece, $c['mode']);
                if ($strategy === 'hub' && $c['source']['code'] !== $hub) {
                    $this->append($groups, 'hub:' . $hub, $hub, $this->rate($p['rates'], $hub, 'outbound', $d), $piece, 'hub');
                }
            }
            if ($remaining) $plan['issues'][] = ['sku'=>$l['sku'], 'code'=>'insufficient_serviceable_stock'];
        }
        // Never return or price a partially fulfillable cart.
        if ($plan['issues']) return $plan;
        ksort($groups);
        $total = 0;
        foreach ($groups as &$g) {
            $g['shipping_minor'] = $this->charge($g['rate'], $g['items'], '');
            $g['estimated_cost_minor'] = $this->charge($g['rate'], $g['items'], 'cost_');
            $g['revenue_owner'] = $g['rate']['revenue_owner'];
            $g['cost_owner'] = $g['rate']['cost_owner'];
            $g['rate_id'] = $g['rate']['id'];
            unset($g['rate']);
            $total += $g['shipping_minor'];
        }
        unset($g);
        return array_replace($plan, ['status'=>'proposed', 'hub'=>$hub ?: null, 'groups'=>array_values($groups), 'shipping_minor'=>$total]);
    }

    private function append(array &$groups, string $key, string $source, array $rate, array $piece, string $mode): void
    {
        $groups[$key] ??= ['id'=>$key, 'source'=>$source, 'leg'=>$rate['leg'], 'mode'=>$mode, 'rate'=>$rate, 'items'=>[]];
        $groups[$key]['items'][] = $piece;
    }

    private function rate(array $rates, string $source, string $leg, array $destination, string $hub = '', array $modes = ['vendor', 'marketplace', 'hub']): ?array
    {
        $matches = array_filter($rates, fn($r) => $r['source'] === $source && $r['leg'] === $leg
            && in_array($r['mode'], $modes, true)
            && ($r['hub'] ?? '') === $hub && $r['destination']['country'] === $destination['country']
            && (!$r['destination']['city_id'] || $r['destination']['city_id'] === $destination['city_id'])
            && (!$r['destination']['locality_id'] || $r['destination']['locality_id'] === $destination['locality_id']));
        usort($matches, fn($a, $b) => [$b['destination']['locality_id'] > 0, $b['destination']['city_id'] > 0, $a['id']]
            <=> [$a['destination']['locality_id'] > 0, $a['destination']['city_id'] > 0, $b['id']]);
        return $matches[0] ?? null;
    }

    private function charge(array $rate, array $items, string $prefix): int
    {
        $milli = $gramMilli = 0;
        foreach ($items as $item) {
            $milli += $item['qty_milli'];
            $gramMilli += $item['weight_grams'] * $item['qty_milli'];
        }
        // Round each group upward once; quantities remain integer thousandths throughout.
        return $rate[$prefix . 'base_minor']
            + intdiv($rate[$prefix . 'per_unit_minor'] * $milli + 999, 1000)
            + $rate[$prefix . 'per_kg_minor'] * intdiv($gramMilli + 999999, 1000000);
    }
}
