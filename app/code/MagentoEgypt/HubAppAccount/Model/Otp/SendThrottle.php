<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Otp;

use Magento\Framework\App\CacheInterface;

/**
 * Fixed-window counters for the app's anonymous writes: WhatsApp code sends per IP and per number
 * (every send is a paid message), and new push tokens per IP.
 *
 * consume() counts one attempt of $subject in $bucket and says how long to wait once the window's
 * limit is reached. Every attempt counts, whether or not anything was sent, so a limit answers the
 * same for a number with an account and one without. Counters live in the default cache (lost on
 * cache:flush, which only resets the limits) under their own tag; subjects are hashed, so no phone
 * number or IP is stored in a cache key.
 */
class SendThrottle
{
    public const CACHE_TAG = 'HM_APP_THROTTLE';

    private const PREFIX = 'hm_app_throttle_';

    public function __construct(
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @param int $limit attempts allowed per window; 0 or less switches the limit off
     * @param int|null $now unix time (tests)
     * @return int 0 when allowed (and counted), else the seconds until the window ends
     */
    public function consume(string $bucket, string $subject, int $limit, int $windowSeconds, ?int $now = null): int
    {
        if ($limit <= 0 || $windowSeconds <= 0) {
            return 0;
        }
        $now ??= time();
        $window = intdiv($now, $windowSeconds);
        $key = self::PREFIX . preg_replace('/\W+/', '_', $bucket) . '_' . sha1($subject) . '_' . $window;

        $count = (int) $this->cache->load($key);
        if ($count >= $limit) {
            return max(1, ($window + 1) * $windowSeconds - $now);
        }
        $this->cache->save((string) ($count + 1), $key, [self::CACHE_TAG], $windowSeconds);

        return 0;
    }
}
