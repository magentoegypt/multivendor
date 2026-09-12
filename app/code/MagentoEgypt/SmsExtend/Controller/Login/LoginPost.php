<?php
namespace MagentoEgypt\SmsExtend\Controller\Login;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use MagentoEgypt\SmsExtend\Helper\Otp as OtpHelper;
use Psr\Log\LoggerInterface;

/**
 * Overrides the Vnecoms SMS login controller so that the storefront "Mobile" tab
 * actually resolves the account from the mobile number the customer typed.
 *
 * The stock loginByOtp() looked the customer up by login[username] (e-mail), which
 * is empty in mobile mode, so every mobile login failed with
 * "A login and a mobile number are required." Here the mobile path resolves the
 * customer via their mobilenumber attribute (format-normalised) and refuses to log
 * in when the number is ambiguous (linked to more than one account).
 */
class LoginPost extends \Vnecoms\Sms\Controller\Login\LoginPost
{
    /**
     * @var AccountConfirmation
     */
    private $smsAccountConfirmation;

    /**
     * @var CustomerUrl
     */
    private $smsCustomerUrl;

    /**
     * @var OtpHelper
     */
    private $otpHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Validator $formKeyValidator,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Vnecoms\Sms\Helper\Data $helperData,
        AccountConfirmation $accountConfirmation,
        CustomerUrl $customerUrl,
        OtpHelper $otpHelper,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $context,
            $resultJsonFactory,
            $customerSession,
            $customerAccountManagement,
            $formKeyValidator,
            $customerRepository,
            $helperData,
            $accountConfirmation,
            $customerUrl
        );
        $this->smsAccountConfirmation = $accountConfirmation;
        $this->smsCustomerUrl = $customerUrl;
        $this->otpHelper = $otpHelper;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        // Bail out on an existing session or an invalid form key.
        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->getRequest())) {
            return $this->resultJsonFactory->create()
                ->setJsonData((new DataObject(['success' => false]))->toJson());
        }

        if ($this->helperData->isEnabledOtpLogin() && $this->isMobileLogin()) {
            $response = $this->loginByMobile();
        } else {
            $response = $this->loginByPassword();
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }

    /**
     * Is this submission the storefront's "Mobile" tab?
     *
     * THIS IS THE FIX FOR ClickUp 86d4azyt1 / CL036-TC20. The branch used to ask
     * only for a POST field named `type` with the value `mobile`, and NOTHING
     * posts that. Vnecoms' own login form - both the customer and the seller
     * template, and the theme's overrides of them - posts
     *
     *     <input type="hidden" name="login_type" value="by_email|by_mobile">
     *
     * driven by login.js through its `loginTypeField` option, using the constants
     * Vnecoms\Sms\Helper\Data::LOGIN_TYPE_EMAIL / LOGIN_TYPE_MOBILE. So `type`
     * was always null, the condition was always false, and every mobile login
     * fell through to loginByPassword() - which requires login[username] and
     * login[password], the two fields the mobile tab HIDES. Hence the reported
     * "email and password are required" on a form where neither was asked for.
     *
     * loginByMobile() below was already correct; it was simply unreachable, which
     * is why this was recorded as fixed and still failed on retest.
     *
     * Reproduced headlessly before the change on BOTH login pages - the customer
     * one at /customer/account/login and the seller one at
     * /marketplace/seller/login, which share this endpoint: switching to Mobile
     * set login_type=by_mobile and hid the e-mail and password inputs, the form
     * POSTed to /vsms/login/loginPost, and the page came back with "A login and a
     * password are required." The seller page is the one QA filed; the customer
     * page had the same defect.
     *
     * `type == 'mobile'` is still honoured so that any other caller of this
     * endpoint - the mobile app's own login, for one - keeps working.
     *
     * @return bool
     */
    private function isMobileLogin()
    {
        if ((string)$this->getRequest()->getPost('type') === 'mobile') {
            return true;
        }

        return (string)$this->getRequest()->getPost('login_type')
            === \Vnecoms\Sms\Helper\Data::LOGIN_TYPE_MOBILE;
    }

    /**
     * Resolve the account from the submitted mobile number and, on success, stash
     * the secure-key => e-mail mapping that Otp\Login\Verify uses to log the
     * customer in once the OTP is confirmed.
     *
     * @return DataObject
     */
    private function loginByMobile()
    {
        $response = new DataObject(['success' => false]);

        $mobile = trim((string)$this->getRequest()->getPost('mobilenumber'));
        if ($mobile === '') {
            $login = (array)$this->getRequest()->getPost('login');
            $mobile = isset($login['mobile']) ? trim((string)$login['mobile']) : '';
        }

        if ($mobile === '') {
            $this->messageManager->addError(__('A login and a mobile number are required.'));
            return $response;
        }

        try {
            $customers = $this->otpHelper->getCustomersByMobile($mobile);

            if (count($customers) === 0) {
                $this->messageManager->addError(__('Please check the mobile number you have entered.'));
                return $response;
            }

            if (count($customers) > 1) {
                $this->messageManager->addError(
                    __('This mobile number is linked to multiple accounts. Please sign in with your email address.')
                );
                return $response;
            }

            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $customers[0];

            if ($customer->getConfirmation()
                && $this->smsAccountConfirmation->isConfirmationRequired(
                    $customer->getWebsiteId(),
                    $customer->getId(),
                    $customer->getEmail()
                )
            ) {
                $value = $this->smsCustomerUrl->getEmailConfirmationUrl($customer->getEmail());
                $this->messageManager->addError(
                    __('This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.', $value)
                );
                return $response;
            }

            // Deliver the OTP to the RESOLVED customer's own stored number, never to the
            // raw input. Otherwise an attacker could match a victim's account using a
            // mangled form of their (non-secret) number while routing the OTP to an
            // attacker-controlled handset -> passwordless account takeover.
            $deliveryNumber = $this->otpHelper->canonicalizeMobileForDelivery($customer->getData('mobilenumber'));
            if ($deliveryNumber === null) {
                $this->messageManager->addError(__('Please check the mobile number you have entered.'));
                return $response;
            }

            $secureKey = md5($customer->getEmail() . md5(time() . rand(1, 1000)));
            $this->session->setData($secureKey, $customer->getEmail());

            $response->setData([
                'success' => true,
                'mobilenumber' => $deliveryNumber,
                'secure_key' => $secureKey,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SmsExtend mobile login failed: ' . $e->getMessage(), ['exception' => $e]);
            $this->messageManager->addError(__('Please check the mobile number you have entered.'));
        }

        return $response;
    }

    /**
     * Verify e-mail + password (the optional 2FA-by-OTP path) without logging the
     * customer in yet. Mirrors the stock behaviour; supro routes plain e-mail
     * logins straight to core customer/account/loginPost, so this only runs when
     * something posts to vsms/login/loginPost without type=mobile.
     *
     * @return DataObject
     */
    private function loginByPassword()
    {
        $response = new DataObject(['success' => false]);

        if (!$this->getRequest()->isPost()) {
            return $response;
        }

        $login = (array)$this->getRequest()->getPost('login');
        if (empty($login['username']) || empty($login['password'])) {
            $this->messageManager->addError(__('A login and a password are required.'));
            return $response;
        }

        $message = null;
        try {
            $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
            /** @var \Magento\Customer\Model\Customer $customerObj */
            $customerObj = $this->_objectManager->create('Magento\Customer\Model\Customer')->updateData($customer);

            if ($customerObj->getConfirmation()
                && $this->smsAccountConfirmation->isConfirmationRequired(
                    $customerObj->getWebsiteId(),
                    $customerObj->getId(),
                    $customerObj->getEmail()
                )
            ) {
                throw new EmailNotConfirmedException(__("This account isn't confirmed. Verify and try again."));
            }

            $secureKey = md5($customerObj->getData('email') . md5(time() . rand(1, 1000)));
            $this->session->setData($secureKey, $customerObj->getData('email'));
            $deliveryNumber = $this->otpHelper->canonicalizeMobileForDelivery($customerObj->getData('mobilenumber'));
            $response->setData([
                'success' => true,
                'mobilenumber' => $deliveryNumber !== null ? $deliveryNumber : $customerObj->getData('mobilenumber'),
                'secure_key' => $secureKey,
            ]);
            return $response;
        } catch (EmailNotConfirmedException $e) {
            $value = $this->smsCustomerUrl->getEmailConfirmationUrl($login['username']);
            $message = __('This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.', $value);
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
            $this->messageManager->addError(__('An unspecified error occurred. Please contact us for assistance.'));
        }

        if ($message !== null) {
            $this->messageManager->addError($message);
            $this->session->setUsername($login['username']);
        }

        return $response;
    }
}
