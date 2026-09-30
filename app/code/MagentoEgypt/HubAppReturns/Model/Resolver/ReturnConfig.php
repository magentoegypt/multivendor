<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppReturns\Model\Rma\ConfigReader;

/**
 * Query.hmReturnConfig — the return form's settings and reasons for the Store header's view. Public.
 */
class ReturnConfig implements ResolverInterface
{
    public function __construct(
        private readonly ConfigReader $configReader
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
        return $this->configReader->read(Caller::storeId($context));
    }
}
