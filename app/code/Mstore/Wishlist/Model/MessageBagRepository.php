<?php declare(strict_types=1);

namespace Mstore\Wishlist\Model;

use Magento\Framework\Model\AbstractModel;
use Mstore\Wishlist\Api\Data\MessageBagInterface;

class MessageBagRepository extends AbstractModel implements MessageBagInterface
{
    /**
     * @inheritDoc
     */
    public function setError($value)
    {
        return $this->setData(MessageBagInterface::ERROR, $value);
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return $this->getData(MessageBagInterface::ERROR);
    }

    /**
     * @inheritDoc
     */
    public function setMessages($value)
    {
        return $this->setData(MessageBagInterface::MESSAGES, $value);
    }

    /**
     * @inheritDoc
     */
    public function getMessages()
    {
        return $this->getData(MessageBagInterface::MESSAGES);
    }
}
