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
use Vnecoms\SmsGraphQl\Model\Sms\VerifyOtp;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class CustomerVerifyOtp implements ResolverInterface
{
    /**
     * @var VerifyOtp
     */
    private $model;

    /**
     * CustomerRegisterOtp constructor.
     * @param VerifyOtp $sendOtp
     */
    public function __construct(
        VerifyOtp $sendOtp
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
        $currentUserId = $context->getUserId();
        $mobileNum = $args["input"]["mobile"];
        $otp = $args["input"]["otp"];
        return $this->model->execute($mobileNum, $otp , $currentUserId, true);
    }
}
