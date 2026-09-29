<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use MagentoEgypt\HubAppAccount\Model\Device\DeviceRegistry;
use MagentoEgypt\HubAppAccount\Model\Device\TokenValidator;

/**
 * Mutation.hmRegisterDevice — upsert the app's FCM token, bound to the customer when the request
 * carries a customer token, as a guest device otherwise. Idempotent; success false only for invalid
 * input (a token refused by the per-IP limit is dropped quietly: the app registers again next launch).
 */
class RegisterDevice implements ResolverInterface
{
    public function __construct(
        private readonly TokenValidator $validator,
        private readonly DeviceRegistry $registry,
        private readonly RemoteAddress $remoteAddress
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
        $device = is_array($input) ? $this->validator->registration($input) : null;
        if ($device === null) {
            return ['success' => false];
        }

        $this->registry->register(
            $device['token'],
            $device['platform'],
            $device['app_version'],
            Caller::customerIdOrNull($context),
            Caller::storeId($context),
            (string) ($this->remoteAddress->getRemoteAddress() ?: 'unknown')
        );

        return ['success' => true];
    }
}
