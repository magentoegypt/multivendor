<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppAccount\Model\Device\DeviceRegistry;
use MagentoEgypt\HubAppAccount\Model\Device\TokenValidator;

/**
 * Mutation.hmUnregisterDevice — switch the token off for this caller (at sign-out, sent with the
 * customer token before revokeCustomerToken, or on opting out). Only the caller's own row is touched:
 * the customer's with a customer token, a guest row without one. Idempotent.
 */
class UnregisterDevice implements ResolverInterface
{
    public function __construct(
        private readonly TokenValidator $validator,
        private readonly DeviceRegistry $registry
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
        $input = $args['input'] ?? null;
        $token = is_array($input) ? $this->validator->unregistration($input) : null;
        if ($token === null) {
            return ['success' => false];
        }
        $this->registry->unregister($token, Caller::customerIdOrNull($context));

        return ['success' => true];
    }
}
