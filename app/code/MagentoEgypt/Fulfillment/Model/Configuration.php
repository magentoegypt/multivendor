<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class Configuration
{
    public function __construct(
        private \Magento\Framework\App\Config\ScopeConfigInterface $config,
        private \Magento\Framework\App\ResourceConnection $resource,
        private Policy $policy
    ) {}

    public function enabled(): bool { return $this->config->isSetFlag('hubfulfillment/general/preview_enabled'); }
    public function checkoutEnabled(): bool { return $this->config->isSetFlag('hubfulfillment/general/checkout_enabled'); }

    public function getJson(): string
    {
        $raw=(string)$this->config->getValue('hubfulfillment/general/policy');
        if (trim($raw)!=='') return $raw;
        return json_encode(['version'=>1,'currency'=>(string)$this->config->getValue('currency/options/base'),'minor_digits'=>2,
            'sources'=>[],'vendors'=>[],'products'=>[],'rates'=>[]],JSON_THROW_ON_ERROR);
    }

    public function get(): array
    {
        try {
            return $this->validate($this->getJson());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Fulfillment policy is invalid.', 0, $e);
        }
    }

    public function validate(string $json): array
    {
        $p = $this->policy->parse($json);
        $db = $this->resource->getConnection();
        $locations = [];
        foreach (array_keys($p['vendors']) as $vendorId) {
            if ($vendorId > 0 && !$db->fetchOne($db->select()->from($this->resource->getTableName('ves_vendor_entity'), 'entity_id')->where('entity_id = ?', $vendorId))) {
                throw new \InvalidArgumentException('Policy vendor does not exist.');
            }
        }
        foreach ($p['sources'] as $source) {
            $exists = $db->fetchOne($db->select()->from($this->resource->getTableName('inventory_source'), 'source_code')->where('source_code = ?', $source['code']));
            if (!$exists) throw new \InvalidArgumentException('Warehouse source does not exist: ' . $source['code']);
            if ($source['vendor_id'] > 0 && !$db->fetchOne($db->select()->from($this->resource->getTableName('ves_vendor_entity'), 'entity_id')->where('entity_id = ?', $source['vendor_id']))) {
                throw new \InvalidArgumentException('Warehouse vendor does not exist.');
            }
            $locations[] = $source['location'];
        }
        foreach ($p['rates'] as $r) $locations[] = $r['destination'];
        foreach ($p['products'] as $product) {
            $owner=$db->fetchOne($db->select()->from($this->resource->getTableName('catalog_product_entity'),'vendor_id')->where('sku = ?', $product['sku']));
            if ($owner === false || (int)$owner !== $product['vendor_id']) throw new \InvalidArgumentException('Product ownership does not match the policy.');
            foreach ($product['coverage'] ?? [] as $area) $locations[]=$area;
        }
        foreach ($locations as $d) {
            foreach (['city_id'=>'city', 'locality_id'=>'locality'] as $key=>$level) {
                if (!$d[$key]) continue;
                $row = $db->fetchRow($db->select()->from($this->resource->getTableName('me_city_location'))->where('location_id = ?', $d[$key]));
                if (!$row || !$row['is_active'] || $row['level'] !== $level || $row['country_id'] !== $d['country']
                    || ($level === 'locality' && (int)$row['parent_id'] !== $d['city_id'])) {
                    throw new \InvalidArgumentException('Invalid or inactive policy geography.');
                }
            }
        }
        return $p;
    }
}
