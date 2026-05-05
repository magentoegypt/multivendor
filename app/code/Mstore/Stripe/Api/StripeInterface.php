<?php

namespace Mstore\Stripe\Api;

/**
 * @api
 */
interface StripeInterface
{
    /**
     * Create Payment Intent
     *
     * @param string $payment_method_id
     * @param string $email
     * @param string $amount
     * @param string $currencyCode
     * @param string $captureMethod
     * @return string
     * @throws \Stripe\Exception\InvalidRequestException
     */
    public function createPaymentIntent($payment_method_id, $email, $amount, $currencyCode, $captureMethod);
}
