<?php
namespace MagentoEgypt\SmsExtend\Model;

use MagentoEgypt\SmsExtend\Api\WhatsAppInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Customer\Model\AuthenticationInterface;

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

    public function __construct(
        \MagentoEgypt\SmsExtend\Helper\Otp $whatsAppHelper,
        AuthenticationInterface $authentication
    ) {
        $this->whatsAppHelper = $whatsAppHelper;
        $this->authentication = $authentication;
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
            /* Any stored spelling of the number counts, not only the exact string sent. */
            $numberInUse = (bool)$this->whatsAppHelper->getCustomersByMobile($mobile);
            if($type == self::LOGIN || $type == self::FORGOTPASS || $type == self::VENDOR_LOGIN || $type == self::VENDOR_FORGOTPASS) {
                if($numberInUse) {
                    $this->whatsAppHelper->sendOtp($mobile);
                } else {
                    $returnData['status'] = 'error';
                    $returnData['message'] = __('Mobile number not found.');
                }
            } else if($type == self::REGISTER || $type == self::UPDATEMOB || $type == self::VENDOR_REGISTER || $type == self::VENDOR_UPDATEMOB) {
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