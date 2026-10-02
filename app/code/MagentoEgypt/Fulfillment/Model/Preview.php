<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class Preview
{
    public function __construct(
        private Configuration $configuration,
        private Planner $planner,
        private \MagentoEgypt\DeliveryAvailability\Model\Availability $availability,
        private \Magento\Catalog\Api\ProductRepositoryInterface $products,
        private \Magento\InventoryApi\Api\GetSourceItemsBySkuInterface $sourceItems,
        private \Magento\InventorySalesApi\Api\StockResolverInterface $stocks,
        private \Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface $salable,
        private \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface $salableQty,
        private \Magento\Framework\App\ResourceConnection $resource,
        private \Magento\Framework\App\Config\ScopeConfigInterface $config,
        private \Magento\Store\Model\StoreManagerInterface $stores
    ) {}

    public function execute(array $input, string $country, int $region, int $city, int $locality, string $strategy): array
    {
        if (!$this->configuration->enabled()) return ['status'=>'disabled', 'reservation'=>'not_reserved'];
        if (!$input || !array_is_list($input) || count($input) > 100) throw new \InvalidArgumentException('Provide 1–100 product lines.');
        $p = $this->configuration->get();
        $store = $this->stores->getStore();
        if ($p['currency'] !== $store->getBaseCurrencyCode()) throw new \LogicException('Fulfillment policy currency does not match store base currency.');
        $location = $this->availability->location($country, $region, $city, $locality);
        $destination = ['country'=>$location['country_id'], 'city_id'=>(int)$location['location_id'],
            'locality_id'=>(int)($location['locality']['location_id'] ?? 0)];
        $stockId = (int)$this->stocks->execute('website', (string)$store->getWebsite()->getCode())->getStockId();
        $db = $this->resource->getConnection();
        $linked = $db->fetchCol($db->select()->from(['l'=>$this->resource->getTableName('inventory_source_stock_link')], 'source_code')
            ->joinInner(['s'=>$this->resource->getTableName('inventory_source')], 's.source_code = l.source_code', [])
            ->where('l.stock_id = ?', $stockId)->where('s.enabled = ?', 1));
        $lines = $inventory = $quantities = [];
        foreach ($input as $i) {
            if (!is_array($i) || !is_string($i['sku'] ?? null) || $i['sku'] === '' || strlen($i['sku']) > 64
                || !is_int($i['qty_milli'] ?? null) || $i['qty_milli'] < 1 || $i['qty_milli'] > 1000000) {
                throw new \InvalidArgumentException('Each line requires a SKU and positive integer qty_milli (1000 = one unit).');
            }
            $quantities[$i['sku']] = ($quantities[$i['sku']] ?? 0) + $i['qty_milli'];
            if ($quantities[$i['sku']] > 1000000) throw new \InvalidArgumentException('Quantity limit exceeded.');
        }
        foreach ($quantities as $sku=>$milli) {
            $product = $this->products->get((string)$sku, false, (int)$store->getId());
            if ((int)$product->getStatus() !== 1 || !in_array((int)$store->getWebsiteId(), array_map('intval', $product->getWebsiteIds()), true)) {
                throw new \InvalidArgumentException('Product is unavailable.');
            }
            // Configurable selections must supply the purchased simple SKU. Complex kits need a dedicated expansion adapter.
            if ($product->getTypeId() !== 'simple' || $product->isVirtual()) return ['status'=>'unsupported_product_type', 'reservation'=>'not_reserved'];
            if ($this->availability->check($location, (string)$sku)['blocked']) return ['status'=>'unavailable', 'reason'=>'delivery_restricted', 'reservation'=>'not_reserved'];
            if (!$this->salable->execute((string)$sku, $stockId, $milli / 1000)->isSalable()
                || $this->salableQty->execute((string)$sku, $stockId) + 0.000001 < $milli / 1000) {
                return ['status'=>'unavailable', 'reason'=>'insufficient_salable_stock', 'reservation'=>'not_reserved'];
            }
            $weight = (float)$product->getWeight();
            $unit = (string)$this->config->getValue('general/locale/weight_unit', 'store', $store->getId());
            $grams = (int)ceil($weight * ($unit === 'lbs' ? 453.59237 : 1000));
            $lines[] = ['sku'=>(string)$sku, 'vendor_id'=>(int)$product->getData('vendor_id'), 'qty_milli'=>$milli, 'weight_grams'=>$grams];
            foreach ($this->sourceItems->execute((string)$sku) as $source) {
                if ((int)$source->getStatus() !== 1 || !in_array($source->getSourceCode(), $linked, true)) continue;
                $inventory[$sku][$source->getSourceCode()] = max(0, (int)floor((float)$source->getQuantity() * 1000 + 0.000001));
            }
        }
        $plan = $this->planner->plan($p, $destination, $lines, $inventory, $strategy);
        // Public contract excludes stock levels, warehouse addresses, cost estimates and settlement data.
        foreach ($plan['groups'] as $index=>&$group) {
            $group['id'] = 'group-' . ($index + 1);
            unset($group['source'], $group['estimated_cost_minor'], $group['cost_owner'], $group['rate_id']);
        }
        unset($group, $plan['hub']);
        $plan['price_basis'] = 'base_currency_excluding_tax';
        $plan['checkout_binding'] = 'preview_only';
        return $plan;
    }
}
