<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppAccount\Model\Otp\WhatsAppSignIn;

/**
 * Mutation.hmSignInWithWhatsAppCode — exchange the WhatsApp code for a customer token (the same kind
 * generateCustomerToken returns). Anonymous; wrong codes count toward the account's lockout; every
 * failure gives the same error. The app then calls mergeCarts as after any sign-in.
 */
class SignInWithWhatsAppCode implements ResolverInterface
{
    public function __construct(
        private readonly WhatsAppSignIn $signIn
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
        return [
            'token' => $this->signIn->signIn(
                (string) ($args['input']['mobile'] ?? ''),
                (string) ($args['input']['code'] ?? '')
            ),
        ];
    }
}
