<?php
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Api;

/**
 * Anonymous email domain check for the storefront forms (CL036-TC97).
 */
interface EmailCheckInterface
{
    /**
     * Can the email's domain receive mail, and did the customer mean another address?
     *
     * @param string $email
     * @return \MagentoEgypt\AccountExtend\Api\Data\EmailCheckResultInterface
     */
    public function check($email);
}
