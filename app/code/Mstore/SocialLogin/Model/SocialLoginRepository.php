<?php
namespace Mstore\SocialLogin\Model;
use Mstore\SocialLogin\Api\SocialLoginInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Customer\Api\Data\CustomerExtensionFactory;

class SocialLoginRepository implements SocialLoginInterface
{
    /**
     * Token Model
     *
     * @var TokenModelFactory
     */
    private $tokenModelFactory;

    /**
     * @type StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @type CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerDataFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var CustomerExtensionFactory
     */
    protected $customerExtensionFactory;
    /**
     * Social constructor.
     * @param CustomerFactory $customerFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param TokenModelFactory $tokenModelFactory
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        TokenModelFactory $tokenModelFactory,
        CustomerExtensionFactory $customerExtensionFactory
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->storeManager = $storeManager;
        $this->tokenModelFactory = $tokenModelFactory;
        $this->customerExtensionFactory = $customerExtensionFactory;
    }

    private function jwtDecode($token){
        $splitToken = explode(".", $token);
        $payloadBase64 = $splitToken[1]; // Payload is always the index 1
        $decodedPayload = json_decode(urldecode(base64_decode($payloadBase64)), true);
        return $decodedPayload;
    }
    
    /**
     * @inheritdoc
     */
    public function login($token, $type)
    {
        if ($type == "facebook") {
            $fields = "id,name,first_name,last_name,email,picture.type(large)";
            $url = 'https://graph.facebook.com/me/?fields='.$fields.'&access_token=' . $token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);

            if (isset($result["email"])) {
                $firstName = $result["first_name"];
                $lastName = $result["last_name"];
                $email = $result["email"];
                $avatar = isset($result["picture"]) &&  isset($result["picture"]["data"]) ? $result["picture"]["data"]["url"] : "";
                return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
            } else {
                throw new InputMismatchException(
                    __("Your 'token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Facebook app.")
                );
            }
        }elseif($type == "google"){
            $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=' . $token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);

            if (isset($result["email"])) {
                $firstName = $result["given_name"];
                $lastName = $result["family_name"];
                $email = $result["email"];
                $avatar = $result["picture"];
                return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
            } else {
                throw new InputMismatchException(
                    __("Your 'token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Google app.")
                );
            }
        }elseif($type == "sms"){
            $url = 'https://graph.accountkit.com/v1.3/me/?access_token=' . $token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);

            if (isset($result["phone"])) {
                $firstName = $result["phone"]["country_prefix"];
                $lastName = $result["phone"]["national_number"];
                $email = $result["phone"]["national_number"]."@mstore.io";
                $avatar = "";
                return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
            } else {
                throw new InputMismatchException(
                    __("Your 'token' did not return phone of the user. Without 'phone' user can't be logged in or registered. Get user phone extended permission while joining the app.")
                );
            }
        }elseif($type == "firebase_sms"){
                $firstName = $token;
                $lastName = "fluxstore";
                $email = $token."@mstore.io";
                $avatar = "";
                return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
        }elseif($type == "apple"){
            $decoded = $this->jwtDecode($token);
			$email = $decoded["email"];
			$firstName = explode("@", $email)[0];
			$lastName = "user";
			$avatar = "";
            return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
        }
    }

    /**
     * @inheritdoc
     */
    public function appleLogin($token, $firstName, $lastName)
    {
        $decoded = $this->jwtDecode($token);
		$email = $decoded["email"];
		$firstName = isset($firstName) && $firstName != null && $firstName != "" ? $firstName : explode("@", $email)[0];
        $lastName = isset($lastName) && $lastName != null && $lastName != "" ? $lastName : "user";
        $avatar = "";
        return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
    }

    private function createSocialLogin($firstName, $lastName, $email, $avatar){
        $customer = $this->customerDataFactory->create();
        $customer->setFirstname($firstName)
                    ->setLastname($lastName)
                    ->setEmail($email)
                    ->setCustomAttribute('customer_avatar',$avatar);
                try {
                    // If customer exists existing hash will be used by Repository
                    $customer = $this->customerRepository->save($customer);
                    $objectManager = ObjectManager::getInstance();
                    $mathRandom = $objectManager->get('Magento\Framework\Math\Random');
                    $newPasswordToken = $mathRandom->getUniqueHash();
                    $accountManagement = $objectManager->get('Magento\Customer\Api\AccountManagementInterface');
                    $accountManagement->changeResetPasswordLinkToken($customer, $newPasswordToken);
                    $token = $this->tokenModelFactory->create()->createCustomerToken($customer->getId())->getToken();
                    return $token;
                } catch (AlreadyExistsException $e) {
                    //email is exist
                    $customer = $this->customerFactory->create();
                    $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
                    $customer->loadByEmail($email);
                    $token = $this->tokenModelFactory->create()->createCustomerToken($customer->getId())->getToken();
                    return $token;
                } catch (Exception $e) {
                    if ($customer->getId()) {
                        $this->_registry->register('isSecureArea', true, true);
                        $this->customerRepository->deleteById($customer->getId());
                    }
                    throw $e;
                }
    }
}