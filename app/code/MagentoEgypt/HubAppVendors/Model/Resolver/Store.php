<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;
use MagentoEgypt\HubAppVendors\Model\Store\StoreCards;
use MagentoEgypt\HubAppVendors\Model\Store\StorePageReader;

/**
 * Query.hmStore(code) — an approved seller's store page; null for any other code.
 *
 * The code is matched case-insensitively, like the website's /shop/<code>. An
 * approved seller with no listable product still has a page (product_count 0),
 * as on the website. The page's products come from core `products` filtered by
 * vendor_id (see the contract note on HmStore).
 */
class Store implements ResolverInterface
{
    public function __construct(
        private readonly SellerDirectory $directory,
        private readonly StoreCards $cards,
        private readonly StorePageReader $pageReader,
        private readonly StorefrontEmulationInterface $emulation
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $code = trim((string) ($args['code'] ?? ''));
        if ($code === '') {
            throw new GraphQlInputException(__('Required parameter "code" is missing.'));
        }

        $vendor = $this->directory->findApprovedByCode($code);
        if ($vendor === null) {
            return null;
        }

        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

        //  One storefront emulation for the name and the dispatch label.
        return $this->emulation->run($storeId, function () use ($vendor, $storeId): ?array {
            $card = $this->cards->complete($this->cards->summaries([$vendor['id']], $storeId), $storeId)[0] ?? null;
            if ($card === null) {
                return null;
            }

            return ['card' => $card] + $this->pageReader->read($vendor['id'], $storeId);
        });
    }
}
