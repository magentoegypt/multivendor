<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppAccount\Model\Credit\CreditAccountReader;
use MagentoEgypt\HubAppAccount\Model\Paging;

/**
 * Query.hmStoreCredit — the signed-in customer's store credit balance and transactions.
 */
class StoreCredit implements ResolverInterface
{
    private const DEFAULT_PAGE_SIZE = 20;
    private const MAX_PAGE_SIZE = 50;

    public function __construct(
        private readonly CreditAccountReader $reader
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

        return $this->reader->account(
            $customerId,
            Caller::storeId($context),
            Paging::fromArgs($args, self::DEFAULT_PAGE_SIZE, self::MAX_PAGE_SIZE)
        );
    }
}
