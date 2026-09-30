<?php
namespace MagentoEgypt\SmsExtend\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Psr\Log\LoggerInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Integration\Api\UserTokenIssuerInterface;
use Magento\Integration\Api\Data\UserTokenParametersInterfaceFactory;
use Magento\Integration\Model\CustomUserContext;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Vnecoms\Sms\Helper\Data as SmsHelper;
use MagentoEgypt\SmsExtend\Model\Otp\MobileNumber;

class Otp extends AbstractHelper
{
    const CACHE_TAG = 'otp_cache_';
    const OTP_EXPIRATION = 900;

    /* Wrong codes allowed per issued OTP; the next one burns it and a new code must be sent. */
    const MAX_VERIFY_ATTEMPTS = 5;
    const ATTEMPTS_TAG = 'otp_attempts_';
    const SENT_TAG = 'otp_sent_';
    const DEFAULT_RESEND_COOLDOWN = 30;

    /* Single-use proof that a number passed VENDOR_REGISTER, for POST /V1/vendors/register. */
    const REGISTRATION_TICKET_TAG = 'otp_regticket_';
    const REGISTRATION_TICKET_LIFETIME = 1800;

    protected $dateTime;
    protected $cache;
    protected $cacheTypeList;
    protected $logger;
    protected $customerCollectionFactory;
    protected $customerRepository;
    protected $tokenFactory;
    protected $filter;
    protected $helper;
    private $tokenIssuer;
    private $tokenParametersFactory;

    public function __construct(
        Context $context,
        DateTime $dateTime,
        Pool $cacheFrontendPool,
        LoggerInterface $logger,
        CollectionFactory $customerCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        TokenFactory $tokenFactory,
        TypeListInterface $cacheTypeList,
        \Magento\Email\Model\Template\Filter $filter,
        SmsHelper $helper,
        UserTokenIssuerInterface $tokenIssuer,
        UserTokenParametersInterfaceFactory $tokenParametersFactory
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->dateTime = $dateTime;
        $this->tokenFactory = $tokenFactory;
        $this->cache = $cacheFrontendPool->get('default');
        $this->cacheTypeList = $cacheTypeList;
        $this->filter = $filter;
        $this->helper = $helper;
        $this->tokenIssuer = $tokenIssuer;
        $this->tokenParametersFactory = $tokenParametersFactory;
    }

    /**
     * One cache key per NUMBER, not per spelling of it: "01001234567", "+201001234567"
     * and "201001234567" share the OTP, its attempt counter and its resend cooldown, and so do
     * "0501234567" and "+971501234567" (MobileNumber::key).
     * (Keyed on the raw string, a new spelling was a fresh counter.)
     */
    private function cacheKey($prefix, $mobile)
    {
        return $prefix . MobileNumber::key((string)$mobile);
    }

    public function getCustomerId($mobile)
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToFilter('mobilenumber', $mobile);
        $customer = $customerCollection->getFirstItem();
        return $customer->getId() ?? null;
    }

    /**
     * Canonical Egyptian E.164 form (+20XXXXXXXXXX) of a value, or null when it is
     * not recognisable as an Egyptian mobile (national = 10 digits starting with "1").
     *
     * @param string $raw
     * @return string|null
     */
    public function normalizeEgyptianMobile($raw)
    {
        return MobileNumber::egyptian((string)$raw);
    }

    /**
     * The stored mobilenumber strings that are the supplied number, to cope with the inconsistent formats
     * in customer_entity.mobilenumber: Egyptian (+20XXXXXXXXXX / 20XXXXXXXXXX / 0XXXXXXXXXX / XXXXXXXXXX)
     * and UAE (+9715XXXXXXXX / 9715XXXXXXXX / 009715XXXXXXXX / 05XXXXXXXX / 5XXXXXXXX) spellings.
     *
     * Every candidate has the supplied number's OTP key (MobileNumber::candidates), so a code sent to
     * the matched account's stored number is the code this number verifies with. A number given as
     * "+<country code>..." for another country matches only itself, and a bare spelling that means a
     * different number (the stored UAE "501234567" for the typed "+501234567") never matches. Strings
     * only: an int is compared by MySQL as a number and matched other spellings.
     *
     * @param string $input
     * @return string[]
     */
    public function normalizeMobileCandidates($input)
    {
        return MobileNumber::candidates((string)$input);
    }

    /**
     * Canonical, deliverable number for sending an OTP to a resolved customer: Egyptian or UAE E.164
     * when derivable, an already-international number as dialled, otherwise null (the caller must
     * refuse to send rather than guess a destination). See MobileNumber::canonical().
     *
     * @param string $stored
     * @return string|null
     */
    public function canonicalizeMobileForDelivery($stored)
    {
        return MobileNumber::canonical((string)$stored);
    }

    /**
     * Resolve every customer whose stored mobile number matches the supplied
     * number (any of the equivalent formats). Returns the matched customer
     * models. More than one result means the number is ambiguous and the caller
     * must NOT log anyone in.
     *
     * @param string $input
     * @return \Magento\Customer\Model\Customer[]
     */
    public function getCustomersByMobile($input)
    {
        $candidates = $this->normalizeMobileCandidates($input);
        if (!$candidates) {
            return [];
        }

        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToFilter('mobilenumber', ['in' => $candidates]);

        $customers = [];
        foreach ($collection as $customer) {
            $customers[$customer->getId()] = $customer;
        }

        return array_values($customers);
    }

    /**
     * Is this number held by ANOTHER account, in any stored spelling (+20…, 20…, 0…, bare, or the exact string)?
     *
     * The one definition behind "Mobile number already exists." (the customer save check, the WhatsApp OTP
     * checks, vendor registration). The account the number is for never counts against itself: a seller saving
     * their own unchanged number was told it was taken, because the format-tolerant match found their own
     * account (TC73 14zb93nv6vw).
     *
     * @param string $mobile
     * @param int|null $customerId the account the number is for (excluded); null for a new account
     * @param int|null $websiteId only that website's accounts, when customer accounts are per website
     * @return bool
     */
    public function isMobileUsedByAnotherAccount($mobile, $customerId = null, $websiteId = null)
    {
        $candidates = $this->normalizeMobileCandidates($mobile);
        $raw = trim((string)$mobile);
        if ($raw === '') {
            return false;
        }
        $candidates[] = $raw;

        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToFilter('mobilenumber', ['in' => array_values(array_unique($candidates))]);
        if ($customerId) {
            $collection->addAttributeToFilter('entity_id', ['neq' => (int)$customerId]);
        }
        if ($websiteId !== null) {
            $collection->addAttributeToFilter('website_id', (int)$websiteId);
        }

        return (bool)$collection->getSize();
    }

    /**
     * A customer token from the CURRENT issuer (JWT on 2.4.4+), the same kind
     * POST /V1/integration/customer/token returns. It used to be minted by the legacy
     * Oauth TokenFactory, which writes an opaque row to oauth_token outside the
     * token-issuer path.
     */
    public function generateToken($customerId)
    {
        $context = new CustomUserContext((int)$customerId, CustomUserContext::USER_TYPE_CUSTOMER);
        return $this->tokenIssuer->create($context, $this->tokenParametersFactory->create());
    }

    public function changePassword($mobile, $password)
    {
        $customers = $this->getCustomersByMobile($mobile);
        if (count($customers) !== 1) {
            throw new LocalizedException(__('Mobile number not found.'));
        }
        $this->changePasswordForCustomer($customers[0]->getId(), $password);
    }

    public function changePasswordForCustomer($customerId, $password)
    {
        $customer = $this->customerCollectionFactory->create()
            ->addAttributeToFilter('entity_id', (int)$customerId)
            ->getFirstItem();
        if ($customer->getId() === null) {
            throw new LocalizedException(__('Mobile number not found.'));
        }
        $customer->changePassword($password);
        $customer->save();
    }

    /**
     * @param string $mobile
     * @return string single-use ticket for POST /V1/vendors/register
     */
    public function issueRegistrationTicket($mobile)
    {
        $ticket = bin2hex(random_bytes(24));
        $canonical = $this->canonicalizeMobileForDelivery($mobile) ?? trim((string)$mobile);
        $this->cache->save(
            $canonical,
            self::REGISTRATION_TICKET_TAG . $ticket,
            [self::CACHE_TAG],
            self::REGISTRATION_TICKET_LIFETIME
        );
        return $ticket;
    }

    /**
     * @param string $ticket
     * @return string|null the verified mobile number, or null when unknown/expired
     */
    public function peekRegistrationTicket($ticket)
    {
        if (!preg_match('/^[a-f0-9]{48}$/', (string)$ticket)) {
            return null;
        }
        $mobile = $this->cache->load(self::REGISTRATION_TICKET_TAG . $ticket);
        return $mobile ?: null;
    }

    public function consumeRegistrationTicket($ticket)
    {
        if (preg_match('/^[a-f0-9]{48}$/', (string)$ticket)) {
            $this->cache->remove(self::REGISTRATION_TICKET_TAG . $ticket);
        }
    }

    /**
     * Generate and store OTP in the cache
     *
     * @param string $phoneNumber
     * @return string
     * @throws LocalizedException
     */
    public function getOtp($phoneNumber)
    {
        $cacheKey = $this->cacheKey(self::CACHE_TAG, $phoneNumber);

        $cachedOtp = $this->cache->load($cacheKey);

        if(!empty($cachedOtp)) return $cachedOtp;

        $otp = random_int(100000, 999999);
        $this->cache->remove($this->cacheKey(self::ATTEMPTS_TAG, $phoneNumber));

        $this->cache->save(
            (string) $otp,
            $cacheKey,
            [self::CACHE_TAG],
            self::OTP_EXPIRATION
        );

        return $otp;
    }

    /**
     * Verify OTP from the cache
     *
     * @param string $phoneNumber
     * @param string $otp
     * @return bool
     * @throws LocalizedException
     */
    public function verifyOtp($phoneNumber, $otp)
    {
        $cacheKey = $this->cacheKey(self::CACHE_TAG, $phoneNumber);
        $attemptsKey = $this->cacheKey(self::ATTEMPTS_TAG, $phoneNumber);

        // Retrieve OTP from cache
        $cachedOtp = $this->cache->load($cacheKey);

        if (!$cachedOtp) {
            throw new LocalizedException(__('OTP has expired or does not exist.'));
        }

        /*
         * A 6-digit code with unlimited guesses for 15 minutes could be brute-forced —
         * and FORGOTPASS sets a new password on success. Each wrong code counts; the
         * MAX_VERIFY_ATTEMPTS-th burns the code. Callers count wrong codes toward the codes'
         * own lock (Model\Otp\OtpGuard), never toward the customer's account lock.
         */
        if (!hash_equals((string)$cachedOtp, trim((string)$otp))) {
            $attempts = (int)$this->cache->load($attemptsKey) + 1;
            if ($attempts >= self::MAX_VERIFY_ATTEMPTS) {
                $this->cache->remove($cacheKey);
                $this->cache->remove($attemptsKey);
                throw new AuthenticationException(__('Too many incorrect codes. Please request a new code.'));
            }
            $this->cache->save((string)$attempts, $attemptsKey, [self::CACHE_TAG], self::OTP_EXPIRATION);
            throw new AuthenticationException(__('Invalid OTP.'));
        }

        // Invalidate the OTP after successful verification
        $this->cache->remove($cacheKey);
        $this->cache->remove($attemptsKey);

        return true;
    }

    public function sendOtp($mobileNum)
    {
        /* Every send is a paid WhatsApp message: one per number per resend period. */
        $cooldown = method_exists($this->helper, 'getOtpResendPeriodTime')
            ? (int)$this->helper->getOtpResendPeriodTime() : 0;
        $cooldown = $cooldown > 0 ? $cooldown : self::DEFAULT_RESEND_COOLDOWN;
        $sentKey = $this->cacheKey(self::SENT_TAG, $mobileNum);
        if ($this->cache->load($sentKey)) {
            throw new LocalizedException(
                __('Please wait %1 seconds before requesting another code.', $cooldown)
            );
        }
        $this->cache->save('1', $sentKey, [self::CACHE_TAG], $cooldown);

        $otp = $this->getOtp($mobileNum);
        /* Send otp Message*/
        $message = $this->helper->getOtpMessage();
        $this->filter->setVariables(['otp_code' => $otp]);
        $message = $this->filter->filter($message);
        $this->helper->sendSms($mobileNum, $message);
        // $this->log($result);
    }

    public function log($data)
    {
        $this->_logger->log('info', 'WHATSAPP', [$data]);
    }
}
