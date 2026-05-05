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
