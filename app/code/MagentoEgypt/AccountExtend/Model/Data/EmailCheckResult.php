<?php
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Model\Data;

use MagentoEgypt\AccountExtend\Api\Data\EmailCheckResultInterface;

class EmailCheckResult implements EmailCheckResultInterface
{
    public function __construct(
        private readonly string $domain = '',
        private readonly bool $deliverable = true,
        private readonly ?string $suggestion = null
    ) {
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getDeliverable()
    {
        return $this->deliverable;
    }

    public function getSuggestion()
    {
        return $this->suggestion;
    }
}
