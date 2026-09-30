<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Model;

use Magento\Framework\App\CacheInterface;

/**
 * Fixed-window counters for anonymous writes: WhatsApp code requests and wrong codes (OtpGuard, for the
 * website's REST service and the app's GraphQL alike), and new push tokens (MagentoEgypt_HubAppAccount).
 * It lives here, in the lowest module that needs it, so SmsExtend depends on no HubApp module.
 *
 * consume() counts one attempt of $subject in $bucket and says how long to wait once the window's limit
 * is reached; peek() only says it, hit() only counts. Counters live in the default cache (lost on
 * cache:flush, which only resets the limits) under their own tag; subjects are hashed, so no phone
 * number or address is stored in a cache key.
 */
class Throttle
{
    public const CACHE_TAG = 'ME_THROTTLE';

    private const PREFIX = 'me_throttle_';

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
        $wait = $this->peek($bucket, $subject, $limit, $windowSeconds, $now);
        if ($wait === 0 && $limit > 0) {
            $this->hit($bucket, $subject, $windowSeconds, $now);
        }

        return $wait;
    }

    /**
     * @return int 0 while the window's count is under $limit (nothing is counted), else the seconds until
     *     the window ends
     */
    public function peek(string $bucket, string $subject, int $limit, int $windowSeconds, ?int $now = null): int
    {
        if ($limit <= 0 || $windowSeconds <= 0) {
            return 0;
        }
        $now ??= time();
        $window = intdiv($now, $windowSeconds);
        if ((int) $this->cache->load($this->key($bucket, $subject, $window)) < $limit) {
            return 0;
        }

        return max(1, ($window + 1) * $windowSeconds - $now);
    }

    /**
     * Counts one attempt in the current window.
     */
    public function hit(string $bucket, string $subject, int $windowSeconds, ?int $now = null): void
    {
        if ($windowSeconds <= 0) {
            return;
        }
        $now ??= time();
        $key = $this->key($bucket, $subject, intdiv($now, $windowSeconds));
        $this->cache->save((string) ((int) $this->cache->load($key) + 1), $key, [self::CACHE_TAG], $windowSeconds);
    }

    private function key(string $bucket, string $subject, int $window): string
    {
        return self::PREFIX . preg_replace('/\W+/', '_', $bucket) . '_' . sha1($subject) . '_' . $window;
    }
}
