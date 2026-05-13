<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api\Data;

/**
 * RMA Message interface.
 * @api
 */
interface MessageInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const MESSAGE_ID      = 'message_id';
    const REQUEST_ID         = 'request_id';
    const MESSAGE       = 'message';
    const ATTACHMENT = 'attachment';
    const TYPE = 'type';
    const FROM = 'from';
    const TO = 'to';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getMessageId();


    /**
     * Get request Id
     *
     * @return string|null
     */
    public function getRequestId();

    /**
     * Get Message
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Get Attachment
     *
     * @return string|null
     */
    public function getAttachments();


    /**
     * Get Type
     *
     * @return string|null
     */
    public function getType();


    /**
     * Get From
     *
     * @return string|null
     */
    public function getFrom();

    /**
     * Get To
     *
     * @return string|null
     */
    public function getTo();

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Get Updated At At
     *
     * @return string|null
     */
    public function getUpdatedAt();


    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setMessageId($id);


    /**
     * Set Request Id
     *
     * @param int $requestId
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setRequestId($requestId);

    /**
     * Set MEssage
     *
     * @param string $content
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setMessage($content);

    /**
     * Set Attachment
     *
     * @param string $attachment
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setAttachment($attachment);

    /**
     * Set Type
     *
     * @param string $type
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setType($type);


    /**
     * Set From
     *
     * @param string $from
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setFrom($from);

    /**
     * Set To
     *
     * @param string $to
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setTo($to);


    /**
     * Set Created At
     *
     * @param string $time
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setCreatedAt($time);

    /**
     * Set Updated At
     *
     * @param string $time
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setUpdatedAt($time);
}
