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
 *
 * A SUCCESSFUL verify clears the counter. It used to count successes too and
 * never reset, so a seller who signed in correctly five times in fifteen minutes
 * was refused with "Too many incorrect attempts" (2026-10-05, testdev8 during the
 * DEV01.44 login tests). Wrong codes still count, and each attempt is still
 * counted BEFORE the controller runs, so a burst of parallel guesses cannot slip
 * past the limit. The app's REST verify (Model\Otp\OtpGuard) already counts only
 * failures and resets on success; this matches it.
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

        $result = $proceed();

        // A correct code is not an "incorrect attempt": start the number afresh.
        if ($this->isSuccess($result)) {
            $this->cache->remove($key);
        }

        return $result;
    }

    /**
     * Did the Vnecoms verify controller sign the visitor in?
     *
     * It answers with a Json result built from setJsonData(), which has no
     * getter, so the body is read from the result's own property. Anything that
     * cannot be read counts as a failure: the counter then stays, as before.
     *
     * @param mixed $result
     */
    private function isSuccess($result): bool
    {
        if (!$result instanceof \Magento\Framework\Controller\Result\Json) {
            return false;
        }

        try {
            $property = new \ReflectionProperty(\Magento\Framework\Controller\Result\Json::class, 'json');
            $property->setAccessible(true);
            $data = json_decode((string) $property->getValue($result), true);
        } catch (\Throwable $e) {
            return false;
        }

        return is_array($data) && !empty($data['success']);
    }
}
