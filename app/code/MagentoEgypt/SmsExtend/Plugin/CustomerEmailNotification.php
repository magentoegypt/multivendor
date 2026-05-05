<?php
namespace MagentoEgypt\SmsExtend\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\EmailNotification;

class CustomerEmailNotification
{
    public function beforeCredentialsChanged(
        EmailNotification $subject,
        CustomerInterface $savedCustomer,
        $origCustomerEmail,
        $isPasswordChanged = false
    ) {
        if($isPasswordChanged || $origCustomerEmail != $savedCustomer->getEmail()) {
            return [$savedCustomer, $origCustomerEmail, $isPasswordChanged];
        }
        return [$savedCustomer, $origCustomerEmail, true];
    }
}