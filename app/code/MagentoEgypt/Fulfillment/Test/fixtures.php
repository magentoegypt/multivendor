<?php
declare(strict_types=1);
// Entirely fictional IDs/rates. Never import into a real store.
function fixturePolicy(): array
{
    $location = fn(int $city) => ['country'=>'EG', 'city_id'=>$city, 'locality_id'=>0];
    $sources = [];
    foreach (['alex'=>[1,101], 'aswan'=>[2,102], 'asyut'=>[3,103], 'hub-giza'=>[0,104]] as $code=>[$owner,$city]) {
        $sources[] = ['code'=>$code, 'vendor_id'=>$owner, 'kind'=>$owner ? 'vendor' : 'hub', 'priority'=>10, 'location'=>$location($city)];
    }
    $rates = [];
    foreach (['alex', 'aswan', 'asyut'] as $i=>$source) {
        $rates[] = fixtureRate($source . '-direct', $source, 'direct', 'vendor', $location(104), 1000 + $i * 1000, 600 + $i * 500, 'vendor', 'vendor');
        $rates[] = fixtureRate($source . '-hub', $source, 'inbound', 'hub', $location(104), 400, 300, 'marketplace', 'marketplace') + ['hub'=>'hub-giza'];
    }
    $rates[] = fixtureRate('hub-lastmile', 'hub-giza', 'outbound', 'hub', $location(104), 1200, 800, 'marketplace', 'marketplace');
    return ['version'=>1, 'currency'=>'EGP', 'minor_digits'=>2, 'sources'=>$sources,
        'vendors'=>[['vendor_id'=>1,'modes'=>['vendor','marketplace','hub']], ['vendor_id'=>2,'modes'=>['vendor','hub']], ['vendor_id'=>3,'modes'=>['vendor','hub']]],
        'products'=>[], 'rates'=>$rates];
}
function fixtureRate(string $id, string $source, string $leg, string $mode, array $destination, int $fee, int $cost, string $revenue, string $costOwner): array
{
    return ['id'=>$id, 'source'=>$source, 'leg'=>$leg, 'mode'=>$mode, 'destination'=>$destination,
        'base_minor'=>$fee, 'per_unit_minor'=>0, 'per_kg_minor'=>0,
        'cost_base_minor'=>$cost, 'cost_per_unit_minor'=>0, 'cost_per_kg_minor'=>0,
        'revenue_owner'=>$revenue, 'cost_owner'=>$costOwner];
}
function fixtureLines(): array
{
    return [
        ['sku'=>'A', 'vendor_id'=>1, 'qty_milli'=>1000, 'weight_grams'=>500],
        ['sku'=>'B', 'vendor_id'=>2, 'qty_milli'=>1000, 'weight_grams'=>1500],
        ['sku'=>'C', 'vendor_id'=>3, 'qty_milli'=>1000, 'weight_grams'=>1000]
    ];
}
function fixtureStock(): array { return ['A'=>['alex'=>10000], 'B'=>['aswan'=>10000], 'C'=>['asyut'=>10000]]; }
