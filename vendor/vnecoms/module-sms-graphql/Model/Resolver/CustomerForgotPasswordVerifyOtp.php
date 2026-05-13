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
use Vnecoms\SmsGraphQl\Model\Sms\VerifyOtpForgotPassword;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class CustomerForgotPasswordVerifyOtp implements ResolverInterface
{
    /**
     * @var VerifyOtpForgotPassword
     */
    private $model;

    /**
     * CustomerRegisterOtp constructor.
     * @param VerifyOtpForgotPassword $sendOtp
     */
    public function __construct(
        VerifyOtpForgotPassword $sendOtp
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
        return $this->model->execute($mobileNum, $otp , $currentUserId);
    }
}
