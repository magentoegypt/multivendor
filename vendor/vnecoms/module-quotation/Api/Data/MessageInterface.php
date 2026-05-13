<?php


namespace Vnecoms\Quotation\Api\Data;

interface MessageInterface
{

    const NAME = 'name';
    const MESSAGE_ID = 'message_id';


    /**
     * Get message_id
     * @return string|null
     */
    
    public function getMessageId();

    /**
     * Set message_id
     * @param string $messageId
     * @return \Vnecoms\Quotation\Api\Data\MessageInterface
     */
    
    public function setMessageId($messageId);

    /**
     * Get name
     * @return string|null
     */
    
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Vnecoms\Quotation\Api\Data\MessageInterface
     */
    
    public function setName($name);
}
