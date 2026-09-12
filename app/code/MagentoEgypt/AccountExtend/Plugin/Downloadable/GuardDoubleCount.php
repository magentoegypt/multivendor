<?php
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Plugin\Downloadable;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item as ItemResource;

/**
 * One click must cost one download (CL036-TC11).
 *
 * THE FAULT. A product bought with Max Downloads = 3 was dead after a single
 * use. Magento's own counting is correct — Download\Link::execute() adds
 * exactly +1 per request — but the access log shows the browser issuing TWO
 * requests a second apart for one click:
 *
 *   08:35:57  GET .../download/link/id/MC4wMjcw…  200  referer: /customer/products/
 *   08:35:58  GET .../download/link/id/MC4wMjcw…  200  referer: the download URL itself
 *
 * Two requests, two decrements. The second carries the download URL as its own
 * referer, so the browser had already navigated there — it is client-side, not
 * a CloudFront retry, which would have repeated the original referer.
 *
 * WHY GUARD RATHER THAN CHASE THE SECOND REQUEST. Whatever provokes it —
 * a double navigation, a prefetch, a retry on a flaky connection, an
 * impatient double-click — the entitlement should survive it. Fixing one
 * browser's behaviour would leave the others. This makes the decrement
 * idempotent for a short window, which holds for all of them.
 *
 * THE DIRECTION OF THE RISK MATTERS. Failing to decrement turns a 3-download
 * product into an unlimited one, which is worse than the bug being fixed. So
 * the guard is deliberately narrow: it fires only on a +1 change to
 * number_of_downloads_used, only within WINDOW_SECONDS, and only for the same
 * purchased link. A genuine second download after the window counts normally.
 * The most it can cost the merchant is one uncounted download by someone who
 * downloads the same file twice inside ten seconds.
 */
class GuardDoubleCount
{
    /**
     * Two seconds. This was ten, and ten proved too generous once the CAUSE of
     * the duplicate was removed: `catalog/downloadable/content_disposition` was
     * sitting at Magento's default `inline`, so Chrome opened the .mpeg link in
     * its media player and the player re-fetched the file - that re-fetch was
     * the second request this guard exists to absorb. With the setting on
     * `attachment` the browser downloads the file instead and one click now
     * makes exactly one request (measured: 17:59:57, one request, one file, one
     * decrement).
     *
     * What the wide window then cost: a shopper who deliberately downloaded the
     * same file twice SEVEN seconds apart got the second one free - measured at
     * 18:05:04 and 18:05:11, two full deliveries, one decrement. That is the
     * failure this class's own header warns about, a 3-download product turning
     * into an unlimited one.
     *
     * Two seconds still covers the duplicate this was built for, which landed
     * one second after the first, while letting any deliberate re-download
     * count. Kept rather than deleted because `attachment` fixes Chrome, not
     * every browser or download manager that might retry.
     */
    private const WINDOW_SECONDS = 2;

    private const KEY_PREFIX = 'hm_downloadable_count_guard_';

    private ?CacheInterface $cache;

    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache ?: ObjectManager::getInstance()->get(CacheInterface::class);
    }

    /**
     * @return array{0: AbstractModel}
     */
    public function beforeSave(ItemResource $subject, AbstractModel $object): array
    {
        $id = (int) $object->getId();
        if (!$id) {
            return [$object];
        }

        $orig = $object->getOrigData('number_of_downloads_used');
        if ($orig === null) {
            return [$object];
        }

        //  Only a +1 on the counter is the controller recording a download.
        //  An admin edit, a status change or anything else passes untouched.
        if ((int) $object->getData('number_of_downloads_used') !== (int) $orig + 1) {
            return [$object];
        }

        $key = self::KEY_PREFIX . $id;

        if ($this->cache->load($key)) {
            //  Already counted a download for this link inside the window, so
            //  this request is the duplicate. Put the counter back, and the
            //  status with it — the controller expires the link on the way past
            //  zero, and an un-decremented count with an expired status would
            //  be worse than either alone.
            $object->setData('number_of_downloads_used', (int) $orig);

            $origStatus = $object->getOrigData('status');
            if ($origStatus !== null) {
                $object->setData('status', $origStatus);
            }

            return [$object];
        }

        $this->cache->save('1', $key, [], self::WINDOW_SECONDS);

        return [$object];
    }
}
