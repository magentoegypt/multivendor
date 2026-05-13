<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\SmsGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Vnecoms\SmsGraphQl\Model\Sms\SendOtp;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class CustomerRegisterOtp implements ResolverInterface
{
    /**
     * @var SendOtp
     */
    private $model;

    /**
     * CustomerRegisterOtp constructor.
     * @param SendOtp $sendOtp
     */
    public function __construct(
        SendOtp $sendOtp
    ) {
        $this->model = $sendOtp;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $result = $this->model->execute($args, $context, true);
        return $result;
    }
}
