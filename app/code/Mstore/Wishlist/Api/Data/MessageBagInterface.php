<?php

namespace Mstore\Wishlist\Api\Data;

interface MessageBagInterface
{
    /**#@+
     * Constants
     */
    const ERROR = 'error';
    /**
     *
     */
    const MESSAGES = 'messages';
    /**#@-*/

    /**
     * Set error
     *
     * @param string $value
     * @return bool
     */
    public function setError($value);

    /**
     * Get error
     *
     * @return boolean
     */
    public function getError();

    /**
     * Set message
     *
     * @param string $value
     * @return bool
     */
    public function setMessages($value);

    /**
     * Get qty
     *
     * @return string[]
     */
    public function getMessages();
}
