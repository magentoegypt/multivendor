<?php

namespace Vnecoms\VendorsSms\Block\Vendors\Config\Form\Field\Mobile;

class Field extends \Vnecoms\Sms\Block\Customer\Register\Mobile
{
    /**
     * @var string
     */
    protected $_template = 'config/mobile.phtml';

    /**
     * Init mobile number
     * 
     * @return string
     */
    public function getInitMobileNumber(){
        return $this->getElement()->getValue();
    }

    /**
     * Send OTP URL
     *
     * @return string
     */
    public function getSendOtpUrl(){
        return $this->_storeManager->getStore()->getUrl('sms/otp/send');
    }

    /**
     * Send OTP URL
     *
     * @return string
     */
    public function getVerifyOtpUrl(){
        return $this->_storeManager->getStore()->getUrl('sms/otp/verify');
    }

    /**
     * Is verified mobile
     *
     * @return boolean
     */
    public function getIsVerifiedMobile(){
        return (bool)$this->getInitMobileNumber();
    }
}
