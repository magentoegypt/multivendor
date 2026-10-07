<?php
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Model;

use MagentoEgypt\AccountExtend\Api\Data\EmailCheckResultInterface;
use MagentoEgypt\AccountExtend\Api\EmailCheckInterface;
use MagentoEgypt\AccountExtend\Model\Data\EmailCheckResult;

/**
 * GET /V1/hm/email-check?email=… — what the storefront's live email hint asks.
 * Read-only: the same EmailDeliverability answer the server-side guards enforce.
 */
class EmailCheck implements EmailCheckInterface
{
    public function __construct(
        private readonly EmailDeliverability $deliverability
    ) {
    }

    public function check($email): EmailCheckResultInterface
    {
        $email = mb_substr(trim((string) $email), 0, 254);
        $result = $this->deliverability->check($email);

        return new EmailCheckResult(
            (string) $result['domain'],
            $result['deliverable'] !== false,
            $result['suggestion']
        );
    }
}
