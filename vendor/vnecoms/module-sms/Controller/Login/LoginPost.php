<?php
namespace Vnecoms\Sms\Controller\Login;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;

class LoginPost extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var \Vnecoms\Sms\Helper\Data
     */
    protected $helperData;

    /**
     * @var AccountConfirmation
     */
    private $accountConfirmation;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;


    /**
     * LoginPost constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Validator $formKeyValidator
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Vnecoms\Sms\Helper\Data $helperData
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Validator $formKeyValidator,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Vnecoms\Sms\Helper\Data $helperData,
        AccountConfirmation $accountConfirmation,
        CustomerUrl $customerUrl
    ) {
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->formKeyValidator = $formKeyValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerRepository = $customerRepository;
        $this->helperData = $helperData;
        $this->accountConfirmation = $accountConfirmation;
        $this->customerUrl = $customerUrl;
        parent::__construct($context);
    }

    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->getRequest())) {
            $response->setData([
                'success' => false
            ]);
        }
        $type = $this->getRequest()->getPost('type');
        if ($this->helperData->isEnabledOtpLogin() && $type == "mobile") {
            $response = $this->loginByOtp();
        } else {
            $response = $this->loginByPassword();
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\DataObject
     */
    private function loginByPassword() {
        $response = new \Magento\Framework\DataObject();
        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                    $customerObj = $this->_objectManager->create('Magento\Customer\Model\Customer')->updateData($customer);


                    if ($customerObj->getConfirmation()
                        && ($this->isConfirmationRequired($customerObj) || $this->isEmailChangedConfirmationRequired($customerObj))) {
                        throw new EmailNotConfirmedException(__("This account isn't confirmed. Verify and try again."));
                    }

                    $secureKey = md5($customerObj->getData('email').md5(time().rand(1,1000)));
                    $this->session->setData($secureKey, $customerObj->getData('email'));
                    $response->setData([
                        'success' => true,
                        'mobilenumber' =>  $customerObj->getData('mobilenumber'),
                        'secure_key' =>  $secureKey,
                    ]);
                    return $response;
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $message = __(
                        'This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.',
                        $value
                    );
                } catch (UserLockedException $e) {
                    $message = __(
                        'The account sign-in was incorrect or your account is disabled temporarily. '
                        . 'Please wait and try again later.'
                    );
                } catch (AuthenticationException $e) {
                    $message = __(
                        'The account sign-in was incorrect or your account is disabled temporarily. '
                        . 'Please wait and try again later.'
                    );
                } catch (LocalizedException $e) {
                    $message = $e->getMessage();
                } catch (\Exception $e) {
                    // PA DSS violation: throwing or logging an exception here can disclose customer password
                    $this->messageManager->addError(
                        __('An unspecified error occurred. Please contact us for assistance.')
                    );
                } finally {
                    if (isset($message)) {
                        $this->messageManager->addError($message);
                        $this->session->setUsername($login['username']);
                    }
                }
            } else {
                $this->messageManager->addError(__('A login and a password are required.'));
            }
        }

        $response->setData([
            'success' => false
        ]);
        return $response;
    }




    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\DataObject
     */
    private function loginByOtp() {
        $response = new \Magento\Framework\DataObject();
        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username'])) {
                try {
                    $customer = $this->customerRepository->get($login['username']);
                    $customerObj = $this->_objectManager->create('Magento\Customer\Model\Customer')->updateData($customer);

                    if ($customerObj->getConfirmation()
                        && ($this->isConfirmationRequired($customerObj) || $this->isEmailChangedConfirmationRequired($customerObj))) {
                        throw new EmailNotConfirmedException(__("This account isn't confirmed. Verify and try again."));
                    }

                    $secureKey = md5($customerObj->getData('email').md5(time().rand(1,1000)));
                    $this->session->setData($secureKey, $customerObj->getData('email'));
                    $response->setData([
                        'success' => true,
                        'mobilenumber' =>  $customerObj->getData('mobilenumber'),
                        'secure_key' =>  $secureKey,
                    ]);
                    return $response;
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $message = __(
                        'This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.',
                        $value
                    );
                } catch (UserLockedException $e) {
                    $message = __(
                        'Please check the mobile number you have entered.'
                    );
                } catch (AuthenticationException $e) {
                    $message = __(
                        'Please check the mobile number you have entered.'
                    );
                } catch (LocalizedException $e) {
                    $message = __(
                        'Please check the mobile number you have entered.'
                    );
                } catch (\Exception $e) {
                    // PA DSS violation: throwing or logging an exception here can disclose customer password
                    $this->messageManager->addError(
                        __('An unspecified error occurred. Please contact us for assistance.')
                    );
                } finally {
                    if (isset($message)) {
                        $this->messageManager->addError($message);
                        $this->session->setUsername($login['username']);
                    }
                }
            } else {
                $this->messageManager->addError(__('A login and a mobile number are required.'));
            }
        }

        $response->setData([
            'success' => false
        ]);
        return $response;
    }


    /**
     * Check if accounts confirmation is required in config
     *
     * @param CustomerInterface $customer
     * @return bool
     * @deprecated 101.0.4
     * @see AccountConfirmation::isConfirmationRequired
     */
    protected function isConfirmationRequired($customer)
    {
        return $this->accountConfirmation->isConfirmationRequired(
            $customer->getWebsiteId(),
            $customer->getId(),
            $customer->getEmail()
        );
    }

    /**
     * Checks if account confirmation is required if the email address has been changed
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    private function isEmailChangedConfirmationRequired(CustomerInterface $customer): bool
    {
        return $this->accountConfirmation->isEmailChangedConfirmationRequired(
            (int)$customer->getWebsiteId(),
            (int)$customer->getId(),
            $customer->getEmail()
        );
    }
}
