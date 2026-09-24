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
     * and "201001234567" share the OTP, its attempt counter and its resend cooldown.
     * (Keyed on the raw string, a new spelling was a fresh counter.)
     */
    private function cacheKey($prefix, $mobile)
    {
        $canonical = $this->canonicalizeMobileForDelivery($mobile);
        return $prefix . preg_replace('/\D+/', '', (string)($canonical ?? $mobile));
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
        $digits = preg_replace('/\D+/', '', (string)$raw);
        if ($digits === '') {
            return null;
        }

        $national = $digits;
        if (strlen($digits) === 12 && strpos($digits, '20') === 0) {
            $national = substr($digits, 2);            // 20XXXXXXXXXX
        } elseif (strlen($digits) === 13 && strpos($digits, '020') === 0) {
            $national = substr($digits, 3);            // 020XXXXXXXXXX
        } elseif (strlen($digits) === 11 && $digits[0] === '0') {
            $national = substr($digits, 1);            // 0XXXXXXXXXX
        }

        if (strlen($national) === 10 && $national[0] === '1') {
            return '+20' . $national;
        }

        return null;
    }

    /**
     * Build the set of stored mobilenumber strings considered equivalent to the
     * supplied number, to cope with the inconsistent formats in
     * customer_entity.mobilenumber (+20XXXXXXXXXX / 20XXXXXXXXXX / 0XXXXXXXXXX / XXXXXXXXXX).
     *
     * Country-aware: a value given in explicit international form for a NON-Egyptian
     * country (leading "+" and not +20) is matched exactly only — it is never folded
     * into an Egyptian national number, so e.g. "+1001234567" cannot resolve an
     * Egyptian "+201001234567" account.
     *
     * @param string $input
     * @return string[]
     */
    public function normalizeMobileCandidates($input)
    {
        $input  = trim((string)$input);
        $digits = preg_replace('/\D+/', '', $input);
        if ($digits === '') {
            return [];
        }

        // Explicit foreign E.164 (+, but not Egypt): exact match only.
        if (isset($input[0]) && $input[0] === '+' && strpos($digits, '20') !== 0) {
            return ['+' . $digits, $digits];
        }

        $candidates = [];
        $eg = $this->normalizeEgyptianMobile($input);
        if ($eg !== null) {
            $national = substr($eg, 3); // drop the leading "+20"
            $candidates['+20' . $national] = true;
            $candidates['20' . $national]  = true;
            $candidates['0' . $national]   = true;
            $candidates[$national]         = true;
        } else {
            $candidates[$digits]       = true;
            $candidates['+' . $digits] = true;
        }

        return array_keys($candidates);
    }

    /**
     * Canonical, deliverable number for sending an OTP to a resolved customer.
     * Returns Egyptian E.164 when derivable, an already-international number as-is,
     * otherwise null (the caller must refuse to send rather than guess a destination).
     *
     * @param string $stored
     * @return string|null
     */
    public function canonicalizeMobileForDelivery($stored)
    {
        $stored = trim((string)$stored);
        if ($stored === '') {
            return null;
        }

        $eg = $this->normalizeEgyptianMobile($stored);
        if ($eg !== null) {
            return $eg;
        }

        if ($stored[0] === '+') {
            $digits = preg_replace('/\D+/', '', $stored);
            return $digits !== '' ? '+' . $digits : null;
        }

        return null;
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
         * MAX_VERIFY_ATTEMPTS-th burns the code. AuthenticationException lets the caller
         * also count it toward the customer's account lockout.
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
