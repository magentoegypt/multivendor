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
use MagentoEgypt\HubAppVendors\Model\Store\SellerReviews;
use MagentoEgypt\HubAppVendors\Model\Store\StoreReviewsQuery;

/**
 * Query.hmStoreReviews(code) — a store page's Reviews tab; null for a code that
 * is not an approved seller, as hmStore.
 *
 * Public and anonymous: send it as GET so the HTTP cache serves it
 * (StoreReviewsIdentity tags it with the seller and the page's products).
 */
class StoreReviews implements ResolverInterface
{
    public function __construct(private readonly StoreReviewsQuery $query)
    {
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
        $pageSize = (int) ($args['pageSize'] ?? 20);
        $currentPage = (int) ($args['currentPage'] ?? 1);
        if ($pageSize < 1 || $pageSize > SellerReviews::MAX_PAGE_SIZE) {
            throw new GraphQlInputException(__('pageSize must be between 1 and %1.', SellerReviews::MAX_PAGE_SIZE));
        }
        if ($currentPage < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

        return $this->query->execute($code, $pageSize, $currentPage, $storeId);
    }
}
