<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api\Data;

/**
 * RMA Queue interface.
 * @api
 */
interface QueueInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const QUEUE_ID      = 'queue_id';
    const EMAIL_SENDER         = 'email_sender';
    const EMAIL_TO       = 'email_to';
    const EMAIL_GATEWAY = 'email_gateway';
    const ADDITION_INFORMATION = 'addition_information';
    const TEMPLATE_ID = 'template_id';
    const STATUS = 'status';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();


    /**
     * Get email sender
     *
     * @return string|null
     */
    public function getEmailSender();

    /**
     * Get email to
     *
     * @return string|null
     */
    public function getEmailTo();

    /**
     * Get addition_information
     *
     * @return string|null
     */
    public function getAdditionInformation();

    /**
     * Get email gateway
     *
     * @return string|null
     */
    public function getEmailGateway();

    /**
     * Get Template Id
     *
     * @return string|null
     */
    public function getTemplateId();


    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\RMA\Api\Data\QueueInterface
     */
    public function setId($id);


    /**
     * Set Email Sender
     *
     * @param string $email
     * @return \Vnecoms\RMA\Api\Data\QueueInterface
     */
    public function setEmailSender($email);

    /**
     * Set email To
     *
     * @param string $email
     * @return \Vnecoms\HelpDesk\Api\Data\QueueInterface
     */
    public function setEmailTo($email);


    /**
     * Set email gate way
     *
     * @param string $email
     * @return \Vnecoms\HelpDesk\Api\Data\QueueInterface
     */
    public function setEmailGateway($email);

    /**
     * Set Addition Information
     *
     * @param string $info
     * @return \Vnecoms\RMA\Api\Data\QueueInterface
     */
    public function setAdditionInformation($info);

    /**
     * Set Template Id
     *
     * @param string $templateId
     * @return \Vnecoms\RMA\Api\Data\QueueInterface
     */
    public function setTemplateId($templateId);


    /**
     * Set status
     *
     * @param string $status
     * @return \Vnecoms\RMA\Api\Data\QueueInterface
     */
    public function setStatus($status);
}
