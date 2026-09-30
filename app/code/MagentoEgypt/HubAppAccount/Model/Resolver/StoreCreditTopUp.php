<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppAccount\Model\Credit\TopUpCatalog;

/**
 * HmStoreCreditAccount.top_up — how credit is bought in the Store header's store view: the store credit
 * products the website's Buy Credit page sells (TopUpCatalog, TopUpOptions); null when it sells none.
 * Its own resolver, so the Account row's balance query never reads the catalogue. The customer was
 * already authorised by hmStoreCredit.
 */
class StoreCreditTopUp implements ResolverInterface
{
    public function __construct(
        private readonly TopUpCatalog $catalog
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        return $this->forStore(Caller::storeId($context));
    }

    /**
     * HmStoreCreditTopUp of store view $storeId, or null when it sells no credit.
     *
     * @return array<string, mixed>|null
     */
    public function forStore(int $storeId): ?array
    {
        $options = $this->catalog->forStore($storeId);
        if ($options === null) {
            return null;
        }
        $currency = $this->catalog->currency($storeId);
        $money = static fn (?float $amount): ?array => $amount === null
            ? null
            : ['value' => round($amount, 2), 'currency' => $currency];

        $presets = [];
        foreach ($options->presets() as $preset) {
            $presets[] = [
                'sku' => $preset['sku'],
                'credit' => $money($preset['credit']),
                'price' => $money($preset['price']),
            ];
        }

        return [
            'sku' => $options->sku(),
            'min' => $money($options->min() === null ? null : (float) $options->min()),
            'max' => $money($options->max() === null ? null : (float) $options->max()),
            'credit_rate' => $options->rate(),
            'presets' => $presets,
        ];
    }
}
