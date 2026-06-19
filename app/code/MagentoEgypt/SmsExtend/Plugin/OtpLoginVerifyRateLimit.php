<?php
namespace MagentoEgypt\SmsExtend\Plugin;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use MagentoEgypt\SmsExtend\Helper\Otp as OtpHelper;

/**
 * Brute-force protection for the storefront OTP-login verify endpoint
 * (Vnecoms\Sms\Controller\Otp\Login\Verify).
 *
 * The OTP is a 6-digit code (1,000,000 combinations) and the stock verify
 * controller imposes no per-target attempt limit, so the code is brute-forceable
 * within its validity window. This caps verification attempts per target mobile
 * number (canonicalised, so format variants share one counter) within a window.
 */
class OtpLoginVerifyRateLimit
{
    /** Max verify attempts per target number per window. */
    const MAX_ATTEMPTS = 5;

    /** Window length in seconds (matches the OTP validity ballpark). */
    const WINDOW = 900;

    const CACHE_PREFIX = 'megp_otp_verify_attempts_';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var OtpHelper
     */
    private $otpHelper;

    public function __construct(
        RequestInterface $request,
        CacheInterface $cache,
        JsonFactory $jsonFactory,
        OtpHelper $otpHelper
    ) {
        $this->request = $request;
        $this->cache = $cache;
        $this->jsonFactory = $jsonFactory;
        $this->otpHelper = $otpHelper;
    }

    /**
     * @param \Vnecoms\Sms\Controller\Otp\Login\Verify $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(\Vnecoms\Sms\Controller\Otp\Login\Verify $subject, callable $proceed)
    {
        $rawMobile = (string)$this->request->getParam('mobile');
        $canonical = $this->otpHelper->canonicalizeMobileForDelivery($rawMobile);
        if ($canonical === null) {
            $canonical = preg_replace('/\D+/', '', $rawMobile);
        }
        if ($canonical === '' || $canonical === null) {
            return $proceed();
        }

        $key = self::CACHE_PREFIX . md5($canonical);
        $attempts = (int)$this->cache->load($key);

        if ($attempts >= self::MAX_ATTEMPTS) {
            return $this->jsonFactory->create()->setJsonData(json_encode([
                'success' => false,
                'msg' => (string)__('Too many incorrect attempts. Please request a new code and try again later.'),
            ]));
        }

        // Count this attempt before delegating so a flood cannot slip through.
        $this->cache->save((string)($attempts + 1), $key, [], self::WINDOW);

        return $proceed();
    }
}
