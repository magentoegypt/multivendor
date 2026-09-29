<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Localization;

use Magento\Framework\App\Area;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Store\Model\App\Emulation;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use Psr\Log\LoggerInterface;

/**
 * Frontend emulation of one store view, re-entrant.
 *
 * Magento\Store\Model\App\Emulation allows ONE level: a second start is ignored
 * and the matching stop would end the outer emulation early. This class counts
 * its own depth, so providers and helpers can each wrap their label building in
 * run() without knowing whether the caller already did.
 *
 * `force` is true: in the graphql area the requested store is usually already
 * the current one, and without force Magento skips the emulation — which is
 * exactly the case where the theme translations are missing.
 *
 * Shared (the default for DI), so the depth counter is per request.
 */
class StorefrontEmulation implements StorefrontEmulationInterface, ResetAfterRequestInterface
{
    private int $depth = 0;

    private ?int $storeId = null;

    public function __construct(
        private readonly Emulation $emulation,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function run(int $storeId, callable $callback): mixed
    {
        if ($this->depth > 0) {
            if ($this->storeId !== $storeId) {
                //  Cannot switch stores mid-emulation without corrupting the outer
                //  environment; the callback runs as the outer store instead.
                $this->logger->warning(sprintf(
                    'HubApp: storefront emulation for store %d requested inside store %d; kept store %d.',
                    $storeId,
                    (int) $this->storeId,
                    (int) $this->storeId
                ));
            }
            $this->depth++;
            try {
                return $callback();
            } finally {
                $this->depth--;
            }
        }

        $started = false;
        try {
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $started = true;
        } catch (\Throwable $e) {
            //  Labels fall back to the graphql area's translations (module CSVs
            //  only) rather than failing the whole response.
            $this->logger->warning('HubApp: storefront emulation unavailable: ' . $e->getMessage());
        }

        $this->depth = 1;
        $this->storeId = $storeId;
        try {
            return $callback();
        } finally {
            $this->depth = 0;
            $this->storeId = null;
            if ($started) {
                try {
                    $this->emulation->stopEnvironmentEmulation();
                } catch (\Throwable $e) {
                    $this->logger->error('HubApp: could not stop storefront emulation: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->depth > 0;
    }

    /**
     * Per-request memo only; nothing survives a request in a long-running process.
     */
    public function _resetState(): void
    {
        $this->depth = 0;
        $this->storeId = null;
    }
}
