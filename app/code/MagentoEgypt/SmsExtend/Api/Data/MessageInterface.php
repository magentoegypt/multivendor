<?php
namespace MagentoEgypt\SmsExtend\Api\Data;

interface MessageInterface
{
    const STATUS = 'status';
    const MESSAGE = 'message';
    const TOKEN = 'token';

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Get token
     *
     * @return string
     */
    public function getToken();

    /**
     * Set token
     *
     * @param string $token
     * @return $this
     */
    public function setToken($token);
}