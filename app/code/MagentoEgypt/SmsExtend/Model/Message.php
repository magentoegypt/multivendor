<?php
namespace MagentoEgypt\SmsExtend\Model;

use MagentoEgypt\SmsExtend\Api\Data\MessageInterface;
use Magento\Framework\Model\AbstractModel;

class Message extends AbstractModel implements MessageInterface
{
    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData(MessageInterface::STATUS);
    }

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(MessageInterface::STATUS, $status);
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getData(MessageInterface::MESSAGE);
    }

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(MessageInterface::MESSAGE, $message);
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getData(MessageInterface::TOKEN);
    }

    /**
     * Set token
     *
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        return $this->setData(MessageInterface::TOKEN, $token);
    }
}