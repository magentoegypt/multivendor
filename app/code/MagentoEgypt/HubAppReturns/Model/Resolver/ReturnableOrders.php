<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppReturns\Model\Rma\EligibilityService;
use MagentoEgypt\HubAppReturns\Model\Rma\Paging;

/**
 * Query.hmReturnableOrders — the signed-in customer's orders with at least one returnable line, newest
 * first, each with the lines the website's return form offers (a bundle's child lines in place of the
 * bundle, ReturnableLines) and how many units of each can be returned now.
 */
class ReturnableOrders implements ResolverInterface
{
    private const DEFAULT_PAGE_SIZE = 10;
    private const MAX_PAGE_SIZE = 20;

    public function __construct(
        private readonly EligibilityService $eligibility
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
        $result = $this->eligibility->returnableOrders($customerId, Caller::storeId($context), $paging);

        return [
            'items' => $result['items'],
            'total_count' => $result['total'],
            'page_info' => $paging->pageInfo($result['total']),
        ];
    }
}
