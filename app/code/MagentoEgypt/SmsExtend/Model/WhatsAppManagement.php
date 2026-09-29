<?php
namespace MagentoEgypt\SmsExtend\Model;

use MagentoEgypt\SmsExtend\Api\WhatsAppInterface;
use MagentoEgypt\SmsExtend\Model\Otp\OtpGuard;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class WhatsAppManagement implements WhatsAppInterface
{
    const LOGIN = 'LOGIN';
    const REGISTER = 'REGISTER';
    const FORGOTPASS = 'FORGOTPASS';
    const UPDATEMOB = 'UPDATEMOB';
    const VENDOR_LOGIN = 'VENDOR_LOGIN';
    const VENDOR_REGISTER = 'VENDOR_REGISTER';
    const VENDOR_FORGOTPASS = 'VENDOR_FORGOTPASS';
    const VENDOR_UPDATEMOB = 'VENDOR_UPDATEMOB';

    /** Flows for an existing account: the code goes to the account's own number, answers never tell who has one. */
    const ACCOUNT_FLOWS = [self::LOGIN, self::FORGOTPASS, self::VENDOR_LOGIN, self::VENDOR_FORGOTPASS];

    /** Flows proving the caller holds a number no other account stores yet. */
    const NEW_NUMBER_FLOWS = [self::REGISTER, self::UPDATEMOB, self::VENDOR_REGISTER, self::VENDOR_UPDATEMOB];

    /**
     * verifyOtp() result key (not part of the REST response, which is status/message/token): seconds before
     * this number's codes can be checked again, when the answer is a lock. The app's GraphQL sign-in reads it.
     */
    const RETRY_AFTER = 'retry_after';

    protected $whatsAppHelper;
    private $authentication;
    private $userContext;
    private $logger;
    private $guard;
    private $remoteAddress;

    public function __construct(
        \MagentoEgypt\SmsExtend\Helper\Otp $whatsAppHelper,
        AuthenticationInterface $authentication,
        ?UserContextInterface $userContext = null,
        ?LoggerInterface $logger = null,
        ?OtpGuard $guard = null,
        ?RemoteAddress $remoteAddress = null
    ) {
        $this->whatsAppHelper = $whatsAppHelper;
        $this->authentication = $authentication;
        /* Optional with a fallback so the compiled DI config keeps working until the next di:compile. */
        $this->userContext = $userContext ?: ObjectManager::getInstance()->get(UserContextInterface::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->guard = $guard ?: ObjectManager::getInstance()->get(OtpGuard::class);
        $this->remoteAddress = $remoteAddress ?: ObjectManager::getInstance()->get(RemoteAddress::class);
    }

    /**
     * The signed-in customer or seller sending the request (bearer token), if any. The OTP route is anonymous,
     * but the app sends its token and Magento resolves it here.
     *
     * @return int|null
     */
    private function callerCustomerId()
    {
        if ((int)$this->userContext->getUserType() !== UserContextInterface::USER_TYPE_CUSTOMER) {
            return null;
        }
        $customerId = (int)$this->userContext->getUserId();
        return $customerId ?: null;
    }

    /**
     * The address the request comes from (Magento's RemoteAddress: behind a CDN it needs the real-client
     * header configured, or every visitor shares the CDN's addresses and its limits).
     *
     * @return string
     */
    private function clientIp()
    {
        $address = $this->remoteAddress->getRemoteAddress();
        return is_string($address) && $address !== '' ? $address : 'unknown';
    }

    /**
     * LOGIN / FORGOTPASS / VENDOR_LOGIN / VENDOR_FORGOTPASS: the code goes to the account's OWN number, or nowhere.
     *
     * The typed number only FINDS the account (getCustomersByMobile(), the same match verifyOtp() uses, accepts any
     * stored spelling). The code used to be sent to the typed string itself, so a spelling that matched an account
     * without being that account's number received the account's code: a number stored bare as "501234567" matched
     * "+501234567" (explicit-foreign branch of Otp::normalizeMobileCandidates), and the code went to +501 (Belize).
     * Whoever held that number could sign in as the customer, or set a new password through FORGOTPASS.
     *
     * Now it goes only to Otp::canonicalizeMobileForDelivery(<number stored on the account>): Egyptian and UAE
     * spellings in international form ("0501234567" -> +971501234567), "+<country code>..." as dialled
     * (MobileNumber::canonical). Nothing is sent when no account or several accounts match (verifyOtp never signs
     * anyone in on an ambiguous number), or when the stored number has no canonical form: refused, never guessed;
     * that customer signs in by e-mail until the stored number is corrected. The OTP cache key is the same for the
     * account's canonical number and for every typed spelling that matches it (MobileNumber::candidates only returns
     * spellings with the typed number's key), so verifyOtp() with the typed number finds the code.
     *
     * The caller gets the same success answer in every case, including the resend cooldown (which only accounts can
     * hit): "Mobile number not found." told anyone which numbers belong to an account.
     *
     * @param string $mobile the number as typed
     * @return void
     */
    private function sendToAccountNumber($mobile)
    {
        $customers = $this->whatsAppHelper->getCustomersByMobile($mobile);
        if (count($customers) !== 1) {
            return;
        }
        $customer = $customers[0];
        $delivery = $this->whatsAppHelper->canonicalizeMobileForDelivery((string)$customer->getData('mobilenumber'));
        if ($delivery === null) {
            $this->logger->warning(sprintf(
                'WhatsApp OTP not sent: the mobile number stored on customer %d has no deliverable international form.',
                (int)$customer->getId()
            ));
            return;
        }
        try {
            $this->whatsAppHelper->sendOtp($delivery);
        } catch (\Exception $e) {
            /* In practice the resend cooldown: the code sent moments ago is still valid. */
            $this->logger->info('WhatsApp OTP not re-sent: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     *
     * Every request of a known type first passes the hourly limits per client address and per number (OtpGuard, the
     * same counters as the app's GraphQL): every code is a paid message, and the anonymous route used to have only
     * the helper's 30-second cooldown per number. The limits count every request, with or without an account, so a
     * limit answers alike for all numbers.
     */
    public function sendOtp($mobile, $type)
    {
        $returnData = [
            'status' => 'success',
            'message' => __('OTP sent successfully. Please check your WhatsApp.')
        ];
        if (empty($mobile) || empty($type)) {
            throw new InputException(__('Invalid input data.'));
        }
        $isAccountFlow = in_array($type, self::ACCOUNT_FLOWS, true);
        if (!$isAccountFlow && !in_array($type, self::NEW_NUMBER_FLOWS, true)) {
            return new DataObject(['status' => 'error', 'message' => __('Invalid input data.')]);
        }
        try {
            $wait = $this->guard->sendWait((string)$mobile, $this->clientIp());
            if ($wait > 0) {
                return new DataObject([
                    'status' => 'error',
                    'message' => __('Too many code requests. Please try again in %1 minutes.', (int)ceil($wait / 60)),
                ]);
            }
            if ($isAccountFlow) {
                /* Always the success answer above, whatever happened: see sendToAccountNumber(). */
                $this->sendToAccountNumber($mobile);
            } else {
                /*
                 * Any stored spelling of the number counts, not only the exact string sent. When a signed-in customer
                 * or seller changes their number (UPDATEMOB / VENDOR_UPDATEMOB), their own account does not count as
                 * "in use": a seller got "Mobile number already exists." for their own number (TC73 14zb93nv6vw).
                 * These flows prove the caller holds a NEW number that no account stores yet, so the code still goes
                 * to the number as sent (unchanged). "Mobile number already exists." tells which numbers have an
                 * account; registration cannot avoid saying so.
                 */
                $updatingOwnNumber = $type == self::UPDATEMOB || $type == self::VENDOR_UPDATEMOB;
                $numberInUse = $this->whatsAppHelper->isMobileUsedByAnotherAccount(
                    $mobile,
                    $updatingOwnNumber ? $this->callerCustomerId() : null
                );
                if(!$numberInUse) {
                    $this->whatsAppHelper->sendOtp($mobile);
                    $returnData['status'] = 'success';
                    $returnData['message'] = __('OTP sent successfully. Please check your WhatsApp.');
                } else {
                    $returnData['status'] = 'error';
                    $returnData['message'] = __('Mobile number already exists.');
                }
            }
        } catch (\Exception $e) {
            $returnData['status'] = 'error';
            $returnData['message'] = $e->getMessage();
        }

        return new DataObject($returnData);
    }

    /**
     * @inheritDoc
     *
     * Checking a code:
     *  - codes have their own lock (OtpGuard): five wrong codes for a number within 15 minutes lock checks for that
     *    number for 15 minutes, and each client address has an hourly budget of wrong codes. Wrong codes no longer
     *    count toward the customer's account lock: anyone could request codes for a victim and send ten wrong ones,
     *    which locked the account, password sign-in included, again every 30 seconds. A right code still clears
     *    that lock (LOGIN, FORGOTPASS), as before: it proves the caller holds the account's number;
     *  - account flows (LOGIN, FORGOTPASS, VENDOR_LOGIN, VENDOR_FORGOTPASS) give ONE answer for no account, several
     *    accounts, and a wrong, expired or burnt code, each counted like a wrong code: "Mobile number not found." and
     *    the "more than one account" message told anyone which numbers have accounts;
     *  - the other flows keep the helper's messages ("Invalid OTP." ...), their wrong codes counted the same way.
     */
    public function verifyOtp($mobile, $otp, $type, $password = "")
    {
        $returnData = [
            'status' => 'success',
            'message' => __('OTP verified successfully.'),
            'token' => ''
        ];
        if (empty($mobile) || empty($otp) || empty($type)) {
            throw new InputException(__('Invalid input data.'));
        }
        $mobile = (string)$mobile;
        $isAccountFlow = in_array($type, self::ACCOUNT_FLOWS, true);
        $clientIp = $this->clientIp();

        try {
            $wait = $this->guard->verifyWait($mobile, $clientIp);
            if ($wait > 0) {
                return $this->refused($this->lockedMessage($wait), $wait);
            }

            $customerId = null;
            if ($isAccountFlow) {
                $customers = $this->whatsAppHelper->getCustomersByMobile($mobile);
                if (count($customers) !== 1) {
                    return $this->wrongCode($mobile, $clientIp, true, null);
                }
                $customerId = (int)$customers[0]->getId();
            }

            try {
                $isVerified = $this->whatsAppHelper->verifyOtp($mobile, $otp);
            } catch (LocalizedException $e) {
                return $this->wrongCode($mobile, $clientIp, $isAccountFlow, $e->getMessage());
            }
            if (!$isVerified) {
                return $this->wrongCode($mobile, $clientIp, $isAccountFlow, (string)__('Invalid OTP.'));
            }
            $this->guard->verifySucceeded($mobile);

            if($type == self::FORGOTPASS || $type == self::VENDOR_FORGOTPASS)
            {
                if(empty($password)) {
                    $returnData['status'] = 'error';
                    $returnData['message'] = __('Password is required.');
                } else {
                    $this->whatsAppHelper->changePasswordForCustomer($customerId, $password);
                    $this->authentication->unlock($customerId);
                    $returnData['message'] = __('Password changes successfully.');
                }
            } else if($type == self::LOGIN || $type == self::VENDOR_LOGIN) {
                $this->authentication->unlock($customerId);
                $returnData['token'] = $this->whatsAppHelper->generateToken($customerId);
            } else if($type == self::VENDOR_REGISTER) {
                /* Proof of the verified number for POST /V1/vendors/register (single use, 30 min). */
                $returnData['token'] = $this->whatsAppHelper->issueRegistrationTicket($mobile);
            }
        } catch (\Exception $e) {
            $returnData['status'] = 'error';
            $returnData['message'] = $e->getMessage();
        }

        return new DataObject($returnData);
    }

    /**
     * A failed check: counted toward the number's lock and the address's budget. Account flows get the one answer
     * that never tells whether the number has an account; the others keep the reason given.
     *
     * @param string $mobile
     * @param string $clientIp
     * @param bool $isAccountFlow
     * @param string|null $reason
     * @return DataObject
     */
    private function wrongCode($mobile, $clientIp, $isAccountFlow, $reason)
    {
        $locked = $this->guard->verifyFailed($mobile, $clientIp);
        if ($locked > 0) {
            return $this->refused($this->lockedMessage($locked), $locked);
        }
        if ($isAccountFlow || $reason === null) {
            return $this->refused(__('That code is incorrect or has expired. Check it, or ask for a new code.'));
        }
        return $this->refused($reason);
    }

    /**
     * @param int $seconds
     * @return \Magento\Framework\Phrase
     */
    private function lockedMessage($seconds)
    {
        return __('Too many incorrect codes. Please try again in %1 minutes.', (int)ceil($seconds / 60));
    }

    /**
     * @param \Magento\Framework\Phrase|string $message
     * @param int $retryAfter
     * @return DataObject
     */
    private function refused($message, $retryAfter = 0)
    {
        return new DataObject([
            'status' => 'error',
            'message' => $message,
            'token' => '',
            self::RETRY_AFTER => (int)$retryAfter,
        ]);
    }
}