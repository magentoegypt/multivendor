<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

/** Accrual preview only. Commission comes from the existing marketplace engine, never a guessed percentage. */
final class Settlement
{
    public function project(array $plan, array $sales): array
    {
        if (($plan['status'] ?? '') !== 'proposed') throw new \InvalidArgumentException('A complete plan is required.');
        $vendors = $entries = [];
        $marketplace = $customer = $carrier = 0;
        foreach ($sales as $sale) {
            foreach (['vendor_id', 'net_goods_minor', 'commission_minor'] as $k) (new Policy())->integer($sale[$k] ?? null, 0, 100000000000);
            $id = $sale['vendor_id'];
            if (isset($vendors[$id]) || $sale['commission_minor'] > $sale['net_goods_minor']) throw new \InvalidArgumentException('Invalid vendor sale or commission.');
            $goods = $sale['net_goods_minor'];
            $commission = $sale['commission_minor'];
            $vendors[$id] = $goods - $commission;
            $marketplace += $commission;
            $customer += $goods;
            $this->pair($entries, 'customer_receivable', 'vendor_payable:' . $id, $goods, 'goods');
            $this->pair($entries, 'vendor_payable:' . $id, 'marketplace_revenue', $commission, 'commission');
        }
        $represented = [];
        foreach ($plan['groups'] as $group) {
            $ids = array_unique(array_column($group['items'], 'vendor_id'));
            foreach ($ids as $id) {
                if (!array_key_exists($id, $vendors)) throw new \InvalidArgumentException('Missing vendor sale.');
                $represented[$id] = true;
            }
            foreach (['revenue_owner', 'cost_owner'] as $key) {
                if ($group[$key] === 'vendor' && count($ids) !== 1) throw new \InvalidArgumentException('Shared shipping cannot be assigned to an arbitrary vendor.');
            }
            $fee = $group['shipping_minor'];
            $cost = $group['estimated_cost_minor'];
            $owner = (int)reset($ids);
            $revenueAccount = $group['revenue_owner'] === 'vendor' ? 'vendor_payable:' . $owner : 'marketplace_revenue';
            $costAccount = $group['cost_owner'] === 'vendor' ? 'vendor_payable:' . $owner : 'marketplace_shipping_expense';
            $this->pair($entries, 'customer_receivable', $revenueAccount, $fee, 'shipping:' . $group['id']);
            $this->pair($entries, $costAccount, 'carrier_payable', $cost, 'shipping_cost:' . $group['id']);
            if ($group['revenue_owner'] === 'vendor') $vendors[$owner] += $fee; else $marketplace += $fee;
            if ($group['cost_owner'] === 'vendor') $vendors[$owner] -= $cost; else $marketplace -= $cost;
            $customer += $fee;
            $carrier += $cost;
        }
        if (count($represented) !== count($vendors)) throw new \InvalidArgumentException('Sales must match the fulfillment vendors.');
        if ($customer !== array_sum($vendors) + $marketplace + $carrier) throw new \LogicException('Settlement does not balance.');
        ksort($vendors);
        return ['status'=>'projection_only', 'currency'=>$plan['currency'], 'customer_due_minor'=>$customer,
            'vendor_payable_minor'=>$vendors, 'marketplace_contribution_minor'=>$marketplace,
            'estimated_carrier_payable_minor'=>$carrier, 'entries'=>$entries,
            'excludes'=>['tax', 'payment_fees', 'vendor_cost_of_goods', 'COD_collection', 'refunds', 'payouts']];
    }

    private function pair(array &$entries, string $debit, string $credit, int $amount, string $reason): void
    {
        if ($amount) $entries[] = ['debit'=>$debit, 'credit'=>$credit, 'amount_minor'=>$amount, 'reason'=>$reason];
    }
}
