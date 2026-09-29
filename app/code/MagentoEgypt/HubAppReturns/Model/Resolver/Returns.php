<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppReturns\Model\Rma\Paging;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;

/**
 * Query.hmReturns — the signed-in customer's returns in the store view's website, newest first.
 */
class Returns implements ResolverInterface
{
    private const DEFAULT_PAGE_SIZE = 20;
    private const MAX_PAGE_SIZE = 50;

    public function __construct(
        private readonly ReturnReader $reader
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
        $customerId = Caller::customerId($context);
        $paging = Paging::fromArgs($args, self::DEFAULT_PAGE_SIZE, self::MAX_PAGE_SIZE);
        $result = $this->reader->page(
            $customerId,
            Caller::websiteId($context),
            Caller::storeId($context),
            $paging
        );

        return [
            'items' => $result['items'],
            'total_count' => $result['total'],
            'page_info' => $paging->pageInfo($result['total']),
        ];
    }
}
