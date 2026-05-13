<?php

namespace Vnecoms\VendorsDomain\Model\Data;


use Vnecoms\VendorsDomain\Api\Data\DomainInterface;

/**
 * Class vendor
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Domain extends \Magento\Framework\Api\AbstractExtensibleObject implements
    \Vnecoms\VendorsDomain\Api\Data\DomainInterface
{
    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::getId()
     */
    public function getId(){
        return $this->_get(self::ID);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::setId()
     */
    public function setId($id){
        return $this->setData(self::ID, $id);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::getType()
     */
    public function getType(){
        return $this->_get(self::TYPE);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::setType()
     */
    public function setType($type){
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::getVendorId()
     */
    public function getVendorId(){
        return $this->_get(self::VENDOR_ID);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::setVendorId()
     */
    public function setVendorId($message){
        return $this->setData(self::VENDOR_ID, $message);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::getDomain()
     */
    public function getDomain(){
        return $this->_get(self::DOMAIN);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::setDomain()
     */
    public function setDomain($domain){
        return $this->setData(self::DOMAIN, $domain);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::getNote()
     */
    public function getNote(){
        return $this->_get(self::NOTE);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::setNote()
     */
    public function setNote($note){
        return $this->setData(self::NOTE, $note);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::getIsSecure()
     */
    public function getIsSecure(){
        return $this->_get(self::IS_SECURE);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::setIsSecure()
     */
    public function setIsSecure($isSecure){
        return $this->setData(self::IS_SECURE, $isSecure);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::getCreatedAt()
     */
    public function getCreatedAt(){
        return $this->_get(self::CREATED_AT);
    }

    /**
     * @see \Vnecoms\VendorsDomain\Api\Data\DomainInterface::setCreatedAt()
     */
    public function setCreatedAt($createdAt){
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getStatus() {
        return $this->_get(self::STATUS);
    }

    /**
     * Set type
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status) {
        return $this->setData(self::STATUS, $status);
    }
}
