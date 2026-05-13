<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api\Data;

/**
 * RMA Request interface.
 * @api
 */
interface RequestInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ID                             = 'entity_id';
    const INCREMENT_ID                   = 'increment_id';
    const WEBSITE_ID                     = 'website_id';
    const PACKAGE_OPENED                 = 'package_opened';
    const TYPE                           = 'type';
    const REASON                         = 'reason';
    const OTHER_REASON                   = 'other_reason';
    const CUSTOMER_ID                    = 'customer_id';
    const CUSTOMER_EMAIL                 = 'customer_email';
    const CUSTOMER_NAME                  = 'customer_name';
    const TOTAL_REPLIES                  = 'total_replies';
    const ORDER_INCREMENTAL_ID           = 'order_incremental_id';
    const IP_ADDESS                      = 'ip_address';
    const STATUS                         = 'status';
    const STATE                          = 'state';
    const NOTE                           = 'note';
    const ADDITION_DATA                  = 'addition_data';
    const TRACKING_CODE                  = 'tracking_code';
    const CREATED_AT                     = 'created_at';
    const UPDATED_AT                     = 'updated_at';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get IncrementId
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Get Website id
     *
     * @return string|null
     */
    public function getWebsiteId();

    /**
     * Get Package Opened
     *
     * @return string|null
     */
    public function getPackageOpened();

    /**
     * Get Type
     *
     * @return string|null
     */
    public function getType();

    /**
     * Get Reason
     *
     * @return string|null
     */
    public function getReason();

    /**
     * Get other reason
     *
     * @return string|null
     */
    public function getOtherReason();

    /**
     * Get customer name
     *
     * @return string|null
     */
    public function getCustomerName();

    /**
     * Get customer_id
     *
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Get customer email
     *
     * @return string|null
     */
    public function getCustomerEmail();

    /**
     * Get total_replies
     *
     * @return string|null
     */
    public function getTotalReplies();

    /**
     * Get order_incremental_id
     *
     * @return string|null
     */
    public function getOrderIncrementalId();

    /**
     * Get ip_address
     *
     * @return string|null
     */
    public function getIpAddress();

    /**
     * Get state
     *
     * @return string|null
     */
    public function getState();


    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Get Note
     *
     * @return string|null
     */
    public function getNote();

    /**
     * Get addition_data
     *
     * @return string|null
     */
    public function getAdditionData();

    /**
     * Get tracking_code
     *
     * @return string|null
     */
    public function getTrackingCode();


    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdatedAt();


    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setId($id);

    /**
     * Set IncrementId
     *
     * @param string $incrementId
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setIncrementId($incrementId);

    /**
     * Set Website Id
     *
     * @param string $websiteId
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setWebsiteId($websiteId);

    /**
     * Set Package Opened
     *
     * @param string $package_opened
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setPackageOpened($package_opened);

    /**
     * Set Type
     *
     * @param string $type
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setType($type);

    /**
     * Set Reason
     *
     * @param string $reason
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setReason($reason);

    /**
     * Set Other Reason
     *
     * @param string $reason
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setOtherReason($reason);


    /**
     * Set Customer Email
     *
     * @param string $email
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setCustomerEmail($email);

    /**
     * Set Customer Name
     *
     * @param string $name
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setCustomerName($name);

    /**
     * Set Customer Id
     *
     * @param string $id
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setCustomerId($id);

    /**
     * Set Total Replies
     *
     * @param string $total_replies
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setTotalReplies($total_replies);

    /**
     * Set Increment Id Order
     *
     * @param string $id
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setOrderIncrementalId($id);

    /**
     * Set Ip Address
     *
     * @param string $ip
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setIpAddress($ip);


    /**
     * Set State
     *
     * @param string $state
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setState($state);

    /**
     * Set Status
     *
     * @param string $state
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setStatus($status);

    /**
     * Set Note
     *
     * @param string $note
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setNote($note);


    /**
     * Set Addition Data
     *
     * @param string $data
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setAdditionData($data);

    /**
     * Set Tracking Code
     *
     * @param string $code
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setTrackingCode($code);


    /**
     * Set creation time
     *
     * @param string $creationTime
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setCreatedAt($creationTime);

    /**
     * Set update time
     *
     * @param string $updateTime
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     */
    public function setUpdatedAt($updateTime);
}
