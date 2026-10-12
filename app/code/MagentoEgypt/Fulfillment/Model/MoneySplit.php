<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class MoneySplit
{
    /** Largest-remainder allocation preserves every minor unit, including partial refunds. */
    public function allocate(int $total, array $weights): array
    {
        if ($total<0 || !$weights) throw new \InvalidArgumentException('Invalid amount or recipients.');
        foreach ($weights as $weight) if (!is_int($weight) || $weight<0) throw new \InvalidArgumentException('Invalid allocation weight.');
        ksort($weights); $sum=array_sum($weights); $result=array_fill_keys(array_keys($weights),0);
        if (!$sum) { if ($total) throw new \InvalidArgumentException('Nonzero amount has zero allocation weight.'); return $result; }
        $remainders=[];
        foreach ($weights as $key=>$weight) {
            if ($total && $weight>intdiv(PHP_INT_MAX,$total)) throw new \OverflowException('Amount exceeds supported range.');
            $result[$key]=intdiv($total*$weight,$sum); $remainders[$key]=($total*$weight)%$sum;
        }
        uksort($remainders,fn($a,$b)=>[$remainders[$b],(string)$a]<=>[$remainders[$a],(string)$b]);
        $remaining=$total-array_sum($result);
        foreach ($remainders as $key=>$unused) { if (!$remaining) break; $result[$key]++; $remaining--; }
        return $result;
    }

    public function weights(array $plan): array
    {
        $weights=[];
        foreach ($plan['groups'] as $g) {
            $ids=array_unique(array_column($g['items'],'vendor_id'));
            if ($g['revenue_owner']==='vendor' && count($ids)!==1) throw new \DomainException('Ambiguous shipping owner.');
            $owner=$g['revenue_owner']==='vendor' ? 'vendor:'.reset($ids) : 'marketplace';
            $weights[$owner]=($weights[$owner]??0)+$g['shipping_minor'];
        }
        return $weights;
    }
}
