<?php
namespace MagentoEgypt\SmsExtend\Model;

use MagentoEgypt\SmsExtend\Api\WhatsAppInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

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

    public function __construct(
        \MagentoEgypt\SmsExtend\Helper\Otp $whatsAppHelper
    ) {
        $this->whatsAppHelper = $whatsAppHelper;
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
            $customerId = $this->whatsAppHelper->getCustomerId($mobile);
            if($type == self::LOGIN || $type == self::FORGOTPASS || $type == self::VENDOR_LOGIN || $type == self::VENDOR_FORGOTPASS) {
                if($customerId) {
                    $this->whatsAppHelper->sendOtp($mobile);
                } else {
                    $returnData['status'] = 'error';
                    $returnData['message'] = __('Mobile number not found.');
                }
            } else if($type == self::REGISTER || $type == self::UPDATEMOB || $type == self::VENDOR_REGISTER || $type == self::VENDOR_UPDATEMOB) {
                if(!$customerId) {
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
            $isVerified = $this->whatsAppHelper->verifyOtp($mobile, $otp);
            if($isVerified) {
                if($type == self::FORGOTPASS || $type == self::VENDOR_FORGOTPASS)
                {
                    if(empty($password)) {
                        $returnData['status'] = 'error';
                        $returnData['message'] = __('Password is required.');
                    } else {
                        $this->whatsAppHelper->changePassword($mobile, $password);
                        $returnData['message'] = __('Password changes successfully.');
                    }
                } else if($type == self::LOGIN || $type == self::VENDOR_LOGIN) {
                    $customerId = $this->whatsAppHelper->getCustomerId($mobile);
                    if(!$customerId) {
                        $returnData['status'] = 'error';
                        $returnData['message'] = __('Mobile number not found.');
                    } else {
                        $returnData['token'] = $this->whatsAppHelper->generateToken($customerId);
                    }
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