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

/**
 * Query.hmReturnableOrder(order_number) — one of the signed-in customer's orders with its lines as
 * hmReturnableOrders lists them, or null when it has nothing to return (not theirs, not processing or
 * complete, everything already returned or held). The order page asks this once to decide whether to
 * offer Return items, instead of paging through hmReturnableOrders.
 */
class ReturnableOrder implements ResolverInterface
{
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

        return $this->eligibility->returnableOrder(
            $customerId,
            Caller::storeId($context),
            (string) ($args['order_number'] ?? '')
        );
    }
}
