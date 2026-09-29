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
use MagentoEgypt\HubAppVendors\Model\Store\StoreListQuery;
use MagentoEgypt\HubAppVendors\Model\Store\StoreSorter;

/**
 * Query.hmStores — approved sellers with at least one listable product, as cards.
 *
 * Public and anonymous: send it as GET so the HTTP cache serves it
 * (StoreListIdentity tags it hm_vendor + hm_vendor_<id>).
 */
class Stores implements ResolverInterface
{
    public function __construct(private readonly StoreListQuery $storeList)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $pageSize = (int) ($args['pageSize'] ?? 20);
        $currentPage = (int) ($args['currentPage'] ?? 1);
        if ($pageSize < 1 || $pageSize > StoreListQuery::MAX_PAGE_SIZE) {
            throw new GraphQlInputException(
                __('pageSize must be between 1 and %1.', StoreListQuery::MAX_PAGE_SIZE)
            );
        }
        if ($currentPage < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        $sort = null;
        if (isset($args['sort']) && $args['sort'] !== '') {
            $sort = StoreSorter::normalise((string) $args['sort']);
            if ($sort === null) {
                throw new GraphQlInputException(__('Unknown store sort "%1".', (string) $args['sort']));
            }
        }

        $input = (array) ($args['filter'] ?? []);
        $filter = [];
        if (array_key_exists('featured', $input) && $input['featured'] !== null) {
            $filter['featured'] = (bool) $input['featured'];
        }
        if (array_key_exists('category_id', $input) && $input['category_id'] !== null) {
            $filter['category_id'] = (int) $input['category_id'];
        }
        if (isset($input['name']) && trim((string) $input['name']) !== '') {
            $filter['name'] = trim((string) $input['name']);
        }
        if (isset($input['codes']) && is_array($input['codes'])) {
            $filter['codes'] = array_map('strval', $input['codes']);
        }

        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

        return $this->storeList->execute($filter, $sort, $pageSize, $currentPage, $storeId);
    }
}
