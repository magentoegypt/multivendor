<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MagentoEgypt\HubApp\Api\CacheTagCleanerInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\ResourceModel\Section as SectionResource;
use Psr\Log\LoggerInterface;

/**
 * Every 10 minutes: purge what time, rather than a save, has made stale.
 *
 * A cached GET lives until it is purged (the HTTP cache TTL is a day by
 * default), so a section scheduled to end at 18:00 would stay on the app Home
 * all evening unless something purges it. This job does, from the table, not
 * from a timer per section:
 *
 *   1. a section's starts_at or ends_at fell in (last run, now]  -> hm_app_home
 *   2. the store's local date changed (Asia/Riyadh midnight): deals start and
 *      end by date                                               -> hm_app_home, hm_app_catalog
 *   3. at most every 30 minutes, the newest order id or the newest product
 *      updated_at moved: best sellers, rankings and seller stats may have
 *      changed. Batched on purpose — the Odoo sync saves products all day and
 *      purging per product save would keep the app's lists permanently cold
 *                                                                -> hm_app_catalog, hm_app_home, hm_vendor
 *
 * The app also hides a section past its ends_at by itself; this makes the
 * server agree within 10 minutes. State lives in the flag hubapp_last_flush.
 */
class FlushExpiredContent
{
    public const FLAG = 'hubapp_last_flush';

    private const CATALOG_CHECK_SECONDS = 1800;

    public function __construct(
        private readonly FlagManager $flagManager,
        private readonly ResourceConnection $resource,
        private readonly CacheTagCleanerInterface $tagCleaner,
        private readonly TimezoneInterface $timezone,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        try {
            $this->run(time());
        } catch (\Throwable $e) {
            $this->logger->error('HubApp: FlushExpiredContent failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * @return string[] the tags purged (for tests and logs)
     */
    public function run(int $now): array
    {
        $state = $this->flagManager->getFlagData(self::FLAG);
        $state = is_array($state) ? $state : [];
        $tags = [];

        //  1. schedule boundaries since the last run (first run: the last 10 minutes)
        $last = (int) ($state['last_run'] ?? 0);
        if ($last <= 0 || $last > $now) {
            $last = $now - 600;
        }
        if ($this->boundaryPassed($last, $now)) {
            $tags[] = Tags::APP_HOME;
        }

        //  2. local midnight
        $today = $this->localDate($now);
        if (isset($state['day']) && $state['day'] !== $today) {
            $tags[] = Tags::APP_HOME;
            $tags[] = Tags::APP_CATALOG;
        }
        $state['day'] = $today;

        //  3. orders or catalogue moved (checked at most every 30 minutes)
        if ($now - (int) ($state['catalog_checked'] ?? 0) >= self::CATALOG_CHECK_SECONDS) {
            $marker = $this->catalogMarker();
            if (isset($state['catalog_marker']) && $state['catalog_marker'] !== $marker) {
                $tags[] = Tags::APP_CATALOG;
                $tags[] = Tags::APP_HOME;
                $tags[] = Tags::VENDOR;
            }
            $state['catalog_marker'] = $marker;
            $state['catalog_checked'] = $now;
        }

        $tags = array_values(array_unique($tags));
        if ($tags) {
            $this->tagCleaner->clean($tags);
            $this->logger->info('HubApp: purged ' . implode(', ', $tags));
        }

        $state['last_run'] = $now;
        $this->flagManager->saveFlag(self::FLAG, $state);

        return $tags;
    }

    private function boundaryPassed(int $from, int $to): bool
    {
        $connection = $this->resource->getConnection();
        $fromUtc = gmdate('Y-m-d H:i:s', $from);
        $toUtc = gmdate('Y-m-d H:i:s', $to);
        $starts = $connection->quoteInto('(starts_at > ?', $fromUtc) . $connection->quoteInto(' AND starts_at <= ?)', $toUtc);
        $ends = $connection->quoteInto('(ends_at > ?', $fromUtc) . $connection->quoteInto(' AND ends_at <= ?)', $toUtc);

        return (int) $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName(SectionResource::TABLE), ['COUNT(*)'])
                ->where('is_active = ?', 1)
                ->where($starts . ' OR ' . $ends)
        ) > 0;
    }

    /**
     * @return array{order: string, product: string}
     */
    private function catalogMarker(): array
    {
        $connection = $this->resource->getConnection();

        return [
            'order' => (string) $connection->fetchOne(
                $connection->select()->from($this->resource->getTableName('sales_order'), ['MAX(entity_id)'])
            ),
            'product' => (string) $connection->fetchOne(
                $connection->select()->from($this->resource->getTableName('catalog_product_entity'), ['MAX(updated_at)'])
            ),
        ];
    }

    private function localDate(int $now): string
    {
        $timezone = (string) $this->timezone->getConfigTimezone() ?: 'UTC';

        return (new \DateTimeImmutable('@' . $now))->setTimezone(new \DateTimeZone($timezone))->format('Y-m-d');
    }
}
