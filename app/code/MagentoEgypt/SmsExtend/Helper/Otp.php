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
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Vnecoms\Sms\Helper\Data as SmsHelper;

class Otp extends AbstractHelper
{
    const CACHE_TAG = 'otp_cache_';
    const OTP_EXPIRATION = 900;

    protected $dateTime;
    protected $cache;
    protected $cacheTypeList;
    protected $logger;
    protected $customerCollectionFactory;
    protected $customerRepository;
    protected $tokenFactory;
    protected $filter;
    protected $helper;

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
        SmsHelper $helper
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

    public function generateToken($customerId)
    {
        return $this->tokenFactory->create()->createCustomerToken($customerId)->getToken();
    }

    public function changePassword($mobile, $password)
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToFilter('mobilenumber', $mobile);
        $customer = $customerCollection->getFirstItem();
        if($customer->getId() === null) {
            throw new LocalizedException(__('Mobile number not found.'));
        }
        $customer->changePassword($password);
        $customer->save();
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
        $cacheKey = self::CACHE_TAG . $phoneNumber;

        $cachedOtp = $this->cache->load($cacheKey);

        if(!empty($cachedOtp)) return $cachedOtp;

        $otp = rand(100000, 999999);

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
        $cacheKey = self::CACHE_TAG . $phoneNumber;

        // Retrieve OTP from cache
        $cachedOtp = $this->cache->load($cacheKey);

        if (!$cachedOtp) {
            throw new LocalizedException(__('OTP has expired or does not exist.'));
        }

        if ($cachedOtp !== $otp) {
            throw new LocalizedException(__('Invalid OTP.'));
        }

        // Invalidate the OTP after successful verification
        $this->cache->remove($cacheKey);

        return true;
    }

    public function sendOtp($mobileNum)
    {
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
