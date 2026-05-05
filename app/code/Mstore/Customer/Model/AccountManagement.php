<?php declare(strict_types=1);

namespace Mstore\Customer\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Mstore\Customer\Api\AccountManagementInterface;

class AccountManagement implements AccountManagementInterface
{
    /**
     * @var AuthenticationInterface
     */
    private $authentication;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var EmailNotificationInterface
     */
    private $emailNotification;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @param AuthenticationInterface $authentication
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param EmailNotificationInterface $emailNotification
     */
    public function __construct(
        AuthenticationInterface $authentication,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        EmailNotificationInterface $emailNotification
    ) {
        $this->authentication = $authentication;
        $this->customerRepository = $customerRepository;
        $this->emailNotification = $emailNotification;
        $this->accountManagement = $accountManagement;
    }

    /**
     * @inheritDoc
     */
    public function changeEmail($customerId, $newEmail, $currentPassword)
    {
        $this->authentication->authenticate($customerId, $currentPassword);
        $customer = $this->customerRepository->getById($customerId);
        $origEmail = $customer->getEmail();
        if ($origEmail == $newEmail) {
            throw new LocalizedException(__('Cannot change the same email.'));
        }
        $customer->setEmail($newEmail);
        $savedCustomer = $this->customerRepository->save($customer);
        //Sending notify email to customer
        $this->sendEmailNotification($savedCustomer, $origEmail);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function changePasswordById($customerId, $currentPassword, $newPassword, $confirmPassword)
    {
        $customer = $this->customerRepository->getById($customerId);
        if ($newPassword != $confirmPassword) {
            throw new InputException(__('Password confirmation doesn\'t match entered password.'));
        }
        $this->accountManagement->changePasswordById($customerId, $currentPassword, $newPassword);
        //Sending notify email to customer
        $this->sendEmailNotification($customer, $customer->getEmail(), true);
        return true;
    }

    /**
     * @param $savedCustomer
     * @param $origCustomerEmail
     * @param bool $isPasswordChanged
     */
    private function sendEmailNotification($savedCustomer, $origCustomerEmail, $isPasswordChanged = false)
    {
        $this->emailNotification->credentialsChanged(
            $savedCustomer,
            $origCustomerEmail,
            $isPasswordChanged
        );
    }
}
