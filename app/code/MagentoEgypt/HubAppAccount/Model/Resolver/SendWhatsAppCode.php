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
use MagentoEgypt\HubAppAccount\Model\Otp\WhatsAppSignIn;

/**
 * Mutation.hmSendWhatsAppCode — send a sign-in code to the matching account's stored number.
 * Anonymous; the same answer for every number unless hubapp/otp/reveal_unknown_number is on
 * (WhatsAppSignIn::sendCode).
 */
class SendWhatsAppCode implements ResolverInterface
{
    public function __construct(
        private readonly WhatsAppSignIn $signIn,
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
        return $this->signIn->sendCode(
            (string) ($args['input']['mobile'] ?? ''),
            (string) ($this->remoteAddress->getRemoteAddress() ?: 'unknown')
        );
    }
}
