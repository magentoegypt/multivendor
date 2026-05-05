<?php

namespace MagentoEgypt\SmsExtend\Api;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\SmsExtend\Api\Data\MessageInterface;

interface WhatsAppInterface
{
    /**
     * Send OTP to a given phone number
     *
     * @param string $mobile
     * @param string $type
     * @throws InputException
     * @throws LocalizedException
     * @return MessageInterface
     */
    public function sendOtp($mobile,$type);

    /**
     * Verify OTP for a given phone number
     *
     * @param string $mobile
     * @param string $otp
     * @param string $type
     * @param string $password
     * @throws InputException
     * @throws LocalizedException
     * @return MessageInterface
     */
    public function verifyOtp($mobile, $otp, $type, $password = "");


}