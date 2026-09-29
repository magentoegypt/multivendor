<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Otp;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MagentoEgypt\SmsExtend\Api\WhatsAppInterface;
use MagentoEgypt\SmsExtend\Helper\Otp;
use MagentoEgypt\SmsExtend\Model\Otp\OtpGuard;
use MagentoEgypt\SmsExtend\Model\WhatsAppManagement;
use Psr\Log\LoggerInterface;
use Vnecoms\Sms\Helper\Data as SmsHelper;

/**
 * Signing in to the app with a code sent on WhatsApp (P2 design 9.5).
 *
 * sendCode():
 *  1. per-address and per-number hourly limits (hubapp/otp/send_limit_*), counted for every request,
 *     so a limit answers alike for numbers with and without an account. SmsExtend's OtpGuard applies
 *     them, on the same counters as the REST service the seller app uses (/V1/whatsapp/otp/send);
 *  2. the account(s) the number matches (Otp::getCustomersByMobile, the match verifyOtp signs in with);
 *  3. the code goes only to the number stored on the one matching account, canonical form
 *     (DeliveryNumber), through Otp::sendOtp (its own resend cooldown, OTP keyed per number);
 *  4. unless hubapp/otp/reveal_unknown_number is on, the answer is the same whatever happened (no
 *     account, several, a stored number that cannot be delivered to, the cooldown, a gateway error):
 *     sent true, one neutral message, the resend cooldown.
 *
 * signIn(): WhatsAppManagement::verifyOtp($mobile, $code, LOGIN) does the work the website's REST
 * sign-in does: the codes' own lock (OtpGuard: five wrong codes lock code sign-in for the number for 15
 * minutes, never the account; a per-address budget of wrong codes), five wrong codes burn the code,
 * ambiguous numbers refused, a JWT from the current token issuer. Every failure answers the same, apart
 * from the lock, which answers alike for every number and says when to try again.
 *
 * The code's cache key is the canonical stored number's digits on send and the typed number's on
 * verify; they are the same digits for every account that can get a code at all (the spelling that
 * matched reduces to the stored number's canonical form).
 */
class WhatsAppSignIn
{
    public const XML_REVEAL_UNKNOWN = 'hubapp/otp/reveal_unknown_number';

    private const MIN_DIGITS = 7;
    private const MAX_DIGITS = 15;

    public function __construct(
        private readonly Otp $otp,
        private readonly DeliveryNumber $deliveryNumber,
        private readonly OtpGuard $guard,
        private readonly WhatsAppInterface $whatsApp,
        private readonly SmsHelper $smsHelper,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{sent: bool, message: string, resend_after_seconds: int} HmSendWhatsAppCodeOutput
     * @throws GraphQlInputException for a value that is not a phone number at all
     */
    public function sendCode(string $mobile, string $clientIp): array
    {
        $mobile = trim($mobile);
        if (!$this->isPhoneNumber($mobile)) {
            throw new GraphQlInputException(
                __('Enter your mobile number with the country code, for example +9715XXXXXXXX.')
            );
        }

        $wait = $this->guard->sendWait($mobile, $clientIp);
        if ($wait > 0) {
            return [
                'sent' => false,
                'message' => (string) __('Too many code requests. Please try again in %1 minutes.', (int) ceil($wait / 60)),
                'resend_after_seconds' => $wait,
            ];
        }

        $stored = [];
        foreach ($this->otp->getCustomersByMobile($mobile) as $customer) {
            $stored[(int) $customer->getId()] = (string) $customer->getData('mobilenumber');
        }
        $delivery = $this->deliveryNumber->resolve($stored);
        $failed = false;
        if ($delivery['number'] !== null) {
            try {
                $this->otp->sendOtp($delivery['number']);
            } catch (LocalizedException $e) {
                //  Otp::sendOtp refuses only inside its resend cooldown; the code sent then is still valid.
                $this->logger->info('HubAppAccount: WhatsApp code not re-sent: ' . $e->getMessage());
            } catch (\Throwable $e) {
                $failed = true;
                $this->logger->error(sprintf(
                    'HubAppAccount: WhatsApp code for customer %d not sent: %s',
                    (int) $delivery['customer_id'],
                    $e->getMessage()
                ));
            }
        } elseif ($delivery['reason'] !== DeliveryNumber::NONE) {
            $this->logger->warning(sprintf(
                'HubAppAccount: WhatsApp code not sent (%s)%s.',
                $delivery['reason'],
                $delivery['customer_id'] ? ', customer ' . $delivery['customer_id'] : ''
            ));
        }

        $cooldown = $this->cooldown();
        if (!$this->scopeConfig->isSetFlag(self::XML_REVEAL_UNKNOWN)) {
            return [
                'sent' => true,
                'message' => (string) __('If this number belongs to an account, we have sent a sign-in code to it on WhatsApp.'),
                'resend_after_seconds' => $cooldown,
            ];
        }

        [$sent, $message] = match (true) {
            $delivery['reason'] === DeliveryNumber::OK && !$failed
                => [true, __('We have sent a sign-in code to your WhatsApp.')],
            $delivery['reason'] === DeliveryNumber::OK
                => [false, __('We couldn\'t send the code. Please try again in a few minutes.')],
            $delivery['reason'] === DeliveryNumber::AMBIGUOUS
                => [false, __('This mobile number is linked to more than one account. Please sign in with your email address.')],
            $delivery['reason'] === DeliveryNumber::UNDELIVERABLE
                => [false, __('We can\'t send a code to the mobile number on this account. Please sign in with your email address.')],
            default
                => [false, __('No account uses this mobile number.')],
        };

        return ['sent' => $sent, 'message' => (string) $message, 'resend_after_seconds' => $cooldown];
    }

    /**
     * @return string customer token (CustomerToken.token)
     * @throws GraphQlInputException|GraphQlAuthenticationException
     */
    public function signIn(string $mobile, string $code): string
    {
        $mobile = trim($mobile);
        $code = trim($code);
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            throw new GraphQlInputException(__('Enter the 6-digit code we sent you on WhatsApp.'));
        }

        $result = null;
        if ($this->isPhoneNumber($mobile)) {
            try {
                $result = $this->whatsApp->verifyOtp($mobile, $code, WhatsAppManagement::LOGIN);
            } catch (\Throwable $e) {
                $this->logger->warning('HubAppAccount: WhatsApp sign-in failed: ' . $e->getMessage());
            }
        }
        if ($result instanceof DataObject && $result->getData('status') === 'success') {
            $token = (string) $result->getData('token');
            if ($token !== '') {
                return $token;
            }
        }
        if ($result instanceof DataObject) {
            //  The reason stays in the log; the caller cannot tell "no account" from "wrong code".
            $this->logger->info('HubAppAccount: WhatsApp sign-in refused: ' . (string) $result->getData('message'));
            $retryAfter = (int) $result->getData(WhatsAppManagement::RETRY_AFTER);
            if ($retryAfter > 0) {
                //  The number's code lock: the same for every number, with or without an account.
                throw new GraphQlAuthenticationException(
                    __('Too many incorrect codes. Please try again in %1 minutes.', (int) ceil($retryAfter / 60))
                );
            }
        }

        throw new GraphQlAuthenticationException(
            __('That code is incorrect or has expired. Check it, or ask for a new code.')
        );
    }

    /**
     * Digits with an optional leading "+" and the usual separators, 7-15 digits (E.164 allows 15).
     */
    private function isPhoneNumber(string $mobile): bool
    {
        if ($mobile === '' || preg_match('/^\+?[0-9 ()\-.]+$/', $mobile) !== 1) {
            return false;
        }
        $digits = strlen((string) preg_replace('/\D+/', '', $mobile));

        return $digits >= self::MIN_DIGITS && $digits <= self::MAX_DIGITS;
    }

    /**
     * The OTP helper's resend cooldown (Vnecoms SMS "OTP resend period", else its 30 s default).
     */
    private function cooldown(): int
    {
        $seconds = method_exists($this->smsHelper, 'getOtpResendPeriodTime')
            ? (int) $this->smsHelper->getOtpResendPeriodTime()
            : 0;

        return $seconds > 0 ? $seconds : Otp::DEFAULT_RESEND_COOLDOWN;
    }
}
