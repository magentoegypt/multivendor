<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\CmsGraphQl\Model\Resolver\DataProvider\Block as BlockProvider;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;

/**
 * HmReturnConfig: the return form's settings as the website reads them (Vnecoms\RMA\Helper\Config,
 * Stores > Configuration > RMA, website scope for the current store view).
 *
 *  - enabled: true whenever this module runs; the website has no switch for customer returns, and
 *    the app hides the feature through hmAppConfig's "returns" flag.
 *  - reasons: the enabled ones only (the hub-market template skips disabled reasons), sorted.
 *  - policy_html: rma/policy/policy_block, rendered like the website's "RMA Policy" box, when
 *    rma/policy/enable_policy is on.
 *  - window_days: always null. The website applies rma/general/order_expiry_day to guests only;
 *    signed-in customers have no window (design 9.3, kept by decision).
 *  - attachment_*: the files a message may carry, by the website's upload rules (AttachmentRules):
 *    the RMA "Allowed file extensions" less the protected ones, the store's upload limit (at most
 *    10 MB), and at most 5 files a message.
 */
class ConfigReader
{
    public function __construct(
        private readonly RmaConfig $rmaConfig,
        private readonly LabelReader $labels,
        private readonly BlockProvider $blocks,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly LoggerInterface $logger,
        private readonly Attachments $attachments
    ) {
    }

    /**
     * @return array<string, mixed> HmReturnConfig
     */
    public function read(int $storeId): array
    {
        $reasons = [];
        foreach ($this->labels->reasons($storeId) as $reason) {
            if ($reason['active']) {
                $reasons[] = ['id' => $reason['id'], 'label' => $reason['label']];
            }
        }
        $attachments = $this->attachments->rules();

        return [
            'enabled' => true,
            'reasons_enabled' => (bool) $this->rmaConfig->enableReasons(),
            'other_reason_allowed' => (bool) $this->rmaConfig->allowOtherReasons(),
            'partial_quantity_allowed' => (bool) $this->rmaConfig->allowPerOrder(),
            'reasons' => $reasons,
            'policy_html' => $this->policy($storeId),
            'window_days' => null,
            'attachment_extensions' => $attachments->extensions,
            'attachment_max_bytes' => $attachments->maxBytes,
            'attachment_max_files' => $attachments->maxFiles,
        ];
    }

    private function policy(int $storeId): ?string
    {
        if (!$this->rmaConfig->enablePolicy()) {
            return null;
        }
        $block = trim((string) $this->rmaConfig->policyBlock());
        if ($block === '') {
            return null;
        }
        try {
            $data = $this->emulation->run($storeId, function () use ($block, $storeId): array {
                return ctype_digit($block)
                    ? $this->blocks->getBlockById((int) $block, $storeId)
                    : $this->blocks->getBlockByIdentifier($block, $storeId);
            });
        } catch (\Throwable $e) {
            //  Missing, disabled or not assigned to this store view: no policy box, as on the website.
            $this->logger->info('HubAppReturns: RMA policy block unavailable: ' . $e->getMessage());

            return null;
        }
        $content = trim((string) ($data['content'] ?? ''));

        return $content !== '' ? $content : null;
    }
}
