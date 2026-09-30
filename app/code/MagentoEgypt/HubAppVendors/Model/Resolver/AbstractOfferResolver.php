<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\DataObject;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\GraphQlCache\Model\CacheableQuery;
use MagentoEgypt\HubAppVendors\Model\Offer\OfferFinder;

/**
 * hm_offer_count and hm_other_offers: ONE OfferFinder call for every product of
 * a response (and one for both fields, which share its per-request result).
 *
 * Public and cacheable with the product (GET). The answer depends on OTHER
 * products — the family's copies, their stock, approval and sellers — so the
 * tags of the whole family (cat_p_<id> of every member, hm_vendor_<id> of every
 * offering seller) are added to the response here rather than through a
 * @cache identity, which for hm_offer_count would only ever see a number.
 */
abstract class AbstractOfferResolver implements BatchResolverInterface
{
    public function __construct(
        protected readonly OfferFinder $finder,
        private readonly CacheableQuery $cacheableQuery
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $productIds = [];
        foreach ($requests as $key => $request) {
            $productIds[$key] = self::productId($request->getValue());
        }
        $wanted = array_values(array_unique(array_filter($productIds)));

        $offers = [];
        if ($wanted) {
            $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
            $offers = $this->finder->offers($wanted, $storeId, $context);
            $this->cacheableQuery->addCacheTags($this->finder->cacheTags($wanted, $storeId));
        }

        $response = new BatchResponse();
        foreach ($requests as $key => $request) {
            $response->addResponse($request, $this->value($offers[$productIds[$key]] ?? [], $request));
        }

        return $response;
    }

    /**
     * The field's value for one product.
     *
     * @param array<int, array<string, mixed>> $offers the product's offers, cheapest first
     */
    abstract protected function value(array $offers, BatchRequestItemInterface $request): mixed;

    /**
     * The product id of a ProductInterface value (0 when there is none).
     *
     * @param array<string, mixed>|null $value
     */
    public static function productId(?array $value): int
    {
        $model = $value['model'] ?? null;
        $id = (int) ($value['entity_id'] ?? ($model instanceof DataObject ? $model->getId() : 0));

        return max(0, $id);
    }
}
