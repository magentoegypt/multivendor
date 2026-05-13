<?php

namespace Vnecoms\VendorsDomain\Api\Data;

interface DomainInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */
    const ID                = 'domain_id';
    const TYPE              = 'type';
    const VENDOR_ID         = 'vendor_id';
    const DOMAIN             = 'domain';
    const NOTE              = 'note';
    const IS_SECURE         = 'is_secure';
    const CREATED_AT        = 'created_at';
    const STATUS        = 'status';

    /**#@-*/

    /**
     * Get id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set vendor id
     *
     * @param int $id
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     */
    public function setId($id);

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Set type (domain or sub_domain)
     *
     * @param string $type
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     */
    public function setType($type);

    /**
     * Get type
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set type
     *
     * @param string $status
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     */
    public function setStatus($status);


    /**
     * Get message
     *
     * @return string
     */
    public function getVendorId();

    /**
     * Set transaction amount
     *
     * @param string $vendorId
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     */
    public function setVendorId($vendorId);

    /**
     * Get additional info
     *
     * @return string
     */
    public function getDomain();

    /**
     * Set additional info
     *
     * @param string $domain
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     */
    public function setDomain($domain);

    /**
     * Get is read
     *
     * @return string
     */
    public function getNote();

    /**
     * Set is read
     *
     * @param string $note
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     */
    public function setNote($note);

    /**
     * Get is reached
     *
     * @return string
     */
    public function getIsSecure();

    /**
     * Set is reached
     *
     * @param string $isSecure
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     */
    public function setIsSecure($isSecure);

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     */
    public function setCreatedAt($createdAt);

}
