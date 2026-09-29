<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;

/**
 * Query.hmReturn(id) — one of the signed-in customer's returns with lines, status history and
 * messages; null when the id is not theirs (no difference between "missing" and "someone else's").
 *
 * Like the website's return page (Vnecoms\VendorsRMA\Controller\Customer\View), reading a return
 * marks it read for the customer, which clears HmReturnSummary.has_unread_reply.
 */
class ReturnDetail implements ResolverInterface
{
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

        return $this->reader->detail($customerId, (int) ($args['id'] ?? 0), Caller::storeId($context));
    }
}
