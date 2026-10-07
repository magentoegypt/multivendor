<?php
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Api\Data;

/**
 * Result of GET /V1/hm/email-check (CL036-TC97).
 */
interface EmailCheckResultInterface
{
    /**
     * The address's domain, lower-cased.
     *
     * @return string
     */
    public function getDomain();

    /**
     * False when the domain cannot receive mail; true when it can or when DNS could not answer.
     *
     * @return bool
     */
    public function getDeliverable();

    /**
     * The address the customer probably meant ("Did you mean …?"), or null.
     *
     * @return string|null
     */
    public function getSuggestion();
}
