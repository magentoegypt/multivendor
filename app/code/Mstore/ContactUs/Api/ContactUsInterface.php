<?php
namespace Mstore\ContactUs\Api;

/**
 * @api
 */
interface ContactUsInterface
{
    /**
     * Contact Us
     *
     * @param string $name
     * @param string $email
     * @param string $phone
     * @param string $comment
     * @return string success
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    public function sendContactUs($name, $email, $phone, $comment);
}