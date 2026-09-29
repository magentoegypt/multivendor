<?php
namespace MagentoEgypt\SmsExtend\Model;

use MagentoEgypt\SmsExtend\Api\WhatsAppInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AuthenticationException;
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

    protected $whatsAppHelper;
    private $authentication;
    private $userContext;
    private $logger;

    public function __construct(
        \MagentoEgypt\SmsExtend\Helper\Otp $whatsAppHelper,
        AuthenticationInterface $authentication,
        ?UserContextInterface $userContext = null,
        ?LoggerInterface $logger = null
    ) {
        $this->whatsAppHelper = $whatsAppHelper;
        $this->authentication = $authentication;
        /* Optional with a fallback so the compiled DI config keeps working until the next di:compile. */
        $this->userContext = $userContext ?: ObjectManager::getInstance()->get(UserContextInterface::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
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
     * The one customer a number belongs to, matched in any of the stored spellings
     * (+20…, 20…, 0…, bare). The old exact-string match missed differently-formatted
     * numbers and took the FIRST row when several customers shared one.
     *
     * @return int|null null = no customer
     * @throws LocalizedException when the number is ambiguous (never sign anyone in then)
     */
    private function resolveCustomerId($mobile)
    {
        $customers = $this->whatsAppHelper->getCustomersByMobile($mobile);
        if (count($customers) > 1) {
            throw new LocalizedException(
                __('This mobile number is linked to more than one account. Please sign in with your email address.')
            );
        }
        return $customers ? (int)$customers[0]->getId() : null;
    }

    /**
     * LOGIN / FORGOTPASS / VENDOR_LOGIN / VENDOR_FORGOTPASS: the code goes to the account's OWN number, or nowhere.
     *
     * The typed number only FINDS the account (getCustomersByMobile(), the same match verifyOtp() uses, accepts any
     * stored spelling). The code used to be sent to the typed string itself, so a spelling that matched an account
     * without being that account's number received the account's code: a number stored bare as "501234567" matches
     * "+501234567" (explicit-foreign branch of Otp::normalizeMobileCandidates), and the code went to +501 (Belize).
     * Whoever held that number could sign in as the customer, or set a new password through FORGOTPASS.
     *
     * Now it goes only to Otp::canonicalizeMobileForDelivery(<number stored on the account>). Nothing is sent when no
     * account or several accounts match (verifyOtp never signs anyone in on an ambiguous number), or when the stored
     * number has no canonical form (neither Egyptian nor "+<country code>..."): refused, never guessed; that customer
     * signs in by e-mail until the stored number is corrected. The OTP cache key is unchanged for every account that
     * does get a code: the canonical stored number and the typed spelling that matched it reduce to the same digits.
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
     */
    public function sendOtp($mobile, $type)
    {
        $returnData = [
            'status' => 'success',
            'message' => __('OTP sent successfully. Please check your WhatsApp.')
        ];
        // Implement the logic to send an OTP via WhatsApp here
        // This is just a placeholder implementation
        if (empty($mobile) || empty($type)) {
            throw new InputException(__('Invalid input data.'));
        }
        try {
            if($type == self::LOGIN || $type == self::FORGOTPASS || $type == self::VENDOR_LOGIN || $type == self::VENDOR_FORGOTPASS) {
                /* Always the success answer above, whatever happened: see sendToAccountNumber(). */
                $this->sendToAccountNumber($mobile);
            } else if($type == self::REGISTER || $type == self::UPDATEMOB || $type == self::VENDOR_REGISTER || $type == self::VENDOR_UPDATEMOB) {
                /*
                 * Any stored spelling of the number counts, not only the exact string sent. When a signed-in customer
                 * or seller changes their number (UPDATEMOB / VENDOR_UPDATEMOB), their own account does not count as
                 * "in use": a seller got "Mobile number already exists." for their own number (TC73 14zb93nv6vw).
                 * These flows prove the caller holds a NEW number that no account stores yet, so the code still goes
                 * to the number as sent (unchanged).
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
            } else {
                $returnData['status'] = 'error';
                $returnData['message'] = __('Invalid input data.');
            }
        } catch (\Exception $e) {
            $returnData['status'] = 'error';
            $returnData['message'] = $e->getMessage();
        }

        return new DataObject($returnData);
    }

    /**
     * @inheritDoc
     */
    public function verifyOtp($mobile, $otp, $type, $password = "")
    {
        $returnData = [
            'status' => 'success',
            'message' => __('OTP verified successfully.'),
            'token' => ''
        ];
        // Implement the logic to verify the OTP here
        // This is just a placeholder implementation
        if (empty($mobile) || empty($otp) || empty($type)) {
            throw new InputException(__('Invalid input data.'));
        }

        try {
            $isAccountFlow = in_array(
                $type,
                [self::LOGIN, self::VENDOR_LOGIN, self::FORGOTPASS, self::VENDOR_FORGOTPASS],
                true
            );
            $customerId = null;
            if ($isAccountFlow) {
                $customerId = $this->resolveCustomerId($mobile);
                if (!$customerId) {
                    throw new LocalizedException(__('Mobile number not found.'));
                }
                /* Same lock as the password sign-in: failed codes count toward it. */
                if ($this->authentication->isLocked($customerId)) {
                    throw new LocalizedException(__(
                        'The account sign-in was incorrect or your account is disabled temporarily. '
                        . 'Please wait and try again later.'
                    ));
                }
            }

            try {
                $isVerified = $this->whatsAppHelper->verifyOtp($mobile, $otp);
            } catch (AuthenticationException $e) {
                if ($customerId) {
                    $this->authentication->processAuthenticationFailure($customerId);
                }
                throw $e;
            }

            if($isVerified) {
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
            } else {
                $returnData['status'] = 'error';
                $returnData['message'] = __('Invalid OTP.');
            }
        } catch (\Exception $e) {
            $returnData['status'] = 'error';
            $returnData['message'] = $e->getMessage();
        }

        return new DataObject($returnData);
    }
}